<?php
/**
 * List all repositories in a Bitbucket workspace, together with access keys, pipeline SSH keys and repository variables
 *
 * Usage (see __construct() docblock for required permissions):
 *     $api = new BitbucketApi($username, $password, $workspace);
 *     $repos = $api->getRepositories();
 *     echo json_encode($repos, JSON_PRETTY_PRINT);
 */
class BitbucketApi
{
    /** @var string */
    const KEY_ERRORS = '_ERRORS_';

    /** @var string */
    protected $bitbucketHost = 'https://api.bitbucket.org';

    /** @var string */
    protected $workspace;

    /** @var resource */
    protected $curlHandler = null;

    /**
     * Constructor
     *
     * @param string $username Bitbucket username
     * @param string $password Bitbucket password. Use app password if 2FA is enabled on account - must have
     *                         admin and read permissions on repositories, plus read permissions on all other resources.
     */
    public function __construct($username, $password, $workspace)
    {
        $this->workspace = $workspace;

        $this->curlHandler = curl_init();
        curl_setopt_array($this->curlHandler, [
            CURLOPT_USERPWD => "{$username}:{$password}",
            CURLOPT_RETURNTRANSFER => true, // return value instead of output to browser
            CURLOPT_HEADER => false, // do not include headers in return value
            CURLOPT_USERAGENT => __CLASS__, // some servers do not accept requests without user agent
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        curl_close($this->curlHandler);
    }

    /**
     * Get list of repositories in workspace
     *
     * @param array $repoSlugs Optional list of repo slugs to restrict to. If not specified, all repos are returned.
     * @return array
     *     [<repoSlug> => {"accessKeys":[],"pipelinesConfig":{"ssh":{"publicKeys":[],"knownHosts":[]},"variables":[]}}]
     */
    public function getRepositories($repoSlugs = [])
    {
        $repoSlugs = is_array($repoSlugs) ? $repoSlugs : [$repoSlugs];
        $endpointUrl = "/2.0/repositories/{$this->workspace}";
        $result = [];

        $errors = [];
        $repos = $this->call($endpointUrl, 'slug');
        foreach ($repos as $key => $value) {
            if (self::KEY_ERRORS === $key) {
                $errors[] = $value;
                continue;
            }

            $slug = $value;
            $slugUrl = "{$endpointUrl}/{$slug}";
            if ($repoSlugs && !in_array($slug, $repoSlugs)) {
                continue;
            }

            // If error response saying "API not found", then try appending / to the endpoint URL
            $deployKeys = $this->call("{$slugUrl}/deploy-keys", ['label', 'last_used', 'key']);
            $publicKeys = $this->call("{$slugUrl}/pipelines_config/ssh/key_pair", 'public_key');
            $knownHosts = $this->call("{$slugUrl}/pipelines_config/ssh/known_hosts/", 'hostname');
            $variables = $this->call("{$slugUrl}/pipelines_config/variables/", ['key', 'value', 'secured']);

            // Look for CHANGELOG.md in root of repository and retrieve YYYY-MM-DD dates
            // Cannot use search function on Bitbucket website cos it strips out "-" from search terms
            $changelog = $this->call("{$slugUrl}/src/master/CHANGELOG.md", [], false); // not JSON
            $changelogDates = [];
            if ($changelog && !isset($changelog[self::KEY_ERRORS])) {
                $matches = [];
                preg_match_all('/(\d{4}\-\d{2}\-\d{2})/', $changelog[0], $matches, PREG_PATTERN_ORDER);
                $changelogDates = $matches[1];
            }

            $result[$slug] = [
                'accessKeys' => $deployKeys,
                'pipelinesConfig' => [
                    'ssh' => [
                        'publicKeys' => $publicKeys,
                        'knownHosts' => $knownHosts,
                    ],
                    'variables' => $variables,
                ],
                'changelogDates' => $changelogDates,
            ];
        }

        if ($errors) {
            $result[self::KEY_ERRORS] = $errors;
        }

        ksort($result);

        return $result;
    }

    /**
     * Call Bitbucket REST API v2.0 endpoint
     *
     * Handles pagination.
     *
     * @param string $endpointUrl Endpoint url without host, e.g. /2.0/repositories
     * @param string|array $extractKeys Optional list of keys to extract from "values" key in responses.
     *                                  If not specified, all keys are extracted.
     * @param bool $isJson Default=true. Whether response from endpoint is in JSON.
     * @return array Concatenation of values for keys from "values" key in paginated responses
     */
    protected function call($endpointUrl, $extractKeys = [], $isJson = true)
    {
        $extractKeys = is_array($extractKeys) ? $extractKeys : [$extractKeys];
        $isSingleExtractKey = (1 === count($extractKeys));
        $result = [];

        // Call endpoints and follow paginated links
        $currentPage = 1;
        $responses = [];
        $errors = [];
        while (true) {
            $endpointUrlForPage = "{$endpointUrl}?page={$currentPage}";
            curl_setopt($this->curlHandler, CURLOPT_URL, "{$this->bitbucketHost}{$endpointUrlForPage}");
            $curlResult = curl_exec($this->curlHandler);
            $response =  json_decode($curlResult, true) ?: [];

            // Check if error response
            $error = $response['error'] ?? [];
            if ($error) {
                // E.g. "Not found" is returned for `ssh/keys` endpoint when no keys are set,
                // but it's not really considered an error, hence not added to errors in this case
                if (($error['message'] ?? '') !== 'Not found') {
                    $errors[] = $error;
                }

                break; // no point continuing with subsequent pages if any
            }

            // Append "values" key to result
            if (!$isJson) {
                $result[] = $curlResult;
            }
            foreach (($response['values'] ?? []) as $value) {
                if ($isSingleExtractKey) {
                    $extractKey = $extractKeys[0];
                    $result[] = $value[$extractKey] ?? null;
                } else {
                    if (!$extractKeys) {
                        $extractKeys = array_keys($value);
                    }

                    $extractedValues = [];
                    foreach ($extractKeys as $extractKey) {
                        $extractedValues[$extractKey] = $value[$extractKey] ?? null;
                    }
                    $result[] = $extractedValues;
                }
            }

            // Check if there is a next page
            $next = $response['next'] ?? '';
            if (!$next || stripos($next, $endpointUrlForPage) !== false) {
                // no next link or next link is same as current link, i.e. no more pages
                break;
            }

            $currentPage++;
        }

        if ($errors) {
            $result[self::KEY_ERRORS] = $errors;
        }

        return $result;
    }
}
