<?php
/**
 * Convert issue numbers in text to JIRA issue keys and titles
 *
 * Notes:
 *   - Different prefixes can be used to correspond to different JIRA project boards,
 *     e.g. t123 can correspond to issue TECH-123, while b456 corresponds to issue BIZ-456.
 *   - A prefix is required to prevent confusion with other numbers used for currency and time.
 *   - E.g. the text "Status of j123: Done" converts to "Status of [MYBOARD-123 My Issue]: Done".
 *   - Note that if any issue number is invalid, the rest of the issue numbers will not
 *     be converted as the JIRA API will return an error response even if the other issues are valid.
 *
 * Usage (see __construct() docblock on username and password):
 *     $app = new JiraTitles($username, $password);
 *     $text = file_get_contents('daily-standup-minutes.txt');
 *     $prefixes = [ // value of "board" key assumed to be same as "project" key if unspecified
 *         't' => ['project' => 'TECH', 'board' => 'TECH'], // e.g. converts t123 to TECH-123
 *         'b' => ['project' => 'BIZ', 'board' => 'BIZ'],   // e.g. converts b456 to BIZ-456
 *     ];
 *     echo $app->convert($text, $prefixes);
 *
 * @link https://github.com/zionsg/standalone-php-scripts/blob/master/JiraTitles/JiraTitles.php
 */
class JiraTitles
{
    /** @var string */
    protected $endpoint = 'https://ivx.atlassian.net/rest/api/3/search';

    /** @var resource */
    protected $curlHandler = null;

    /**
     * Constructor
     *
     * @param string $username JIRA username, usually the email address
     * @param string $password API token. Login to JIRA, go to Personal Settings (top righthand corner),
     *                         Security, API token.
     */
    public function __construct($username, $password)
    {
        $this->curlHandler = curl_init();
        curl_setopt_array($this->curlHandler, [
            CURLOPT_USERPWD => "{$username}:{$password}",
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
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
     * Convert issue numbers to JIRA issue keys and titles
     *
     * Note that if any issue number is invalid, the rest of the issue numbers will not
     * be converted as the JIRA API will return an error response even if the other issues are valid.
     *
     * @param string $inputText
     * @param array $prefixes [<prefix> => ['project' => <projectName>, 'board' => <boardName]]
     * @param string $projectName JIRA project
     * @param string $boardName JIRA board
     * @return string Text with issue numbers converted. If issues are not found or there are
     *                errors, error messages will be prepended to the text.
     */
    public function convert($inputText, $prefixes)
    {
        $outputText = $inputText;

        foreach ($prefixes as $prefix => $info) {
            // Find JIRA issue numbers
            $pattern = "/({$prefix}\d+)/";
            $projectName = $info['project'] ?? '';
            $boardName = $info['board'] ?? $projectName;
            if (!$projectName || !$boardName) {
                $outputText = $this->getErrorMessage($prefix, 'No project/board name specified') . $outputText;
                continue;
            }

            $matches = [];
            if (!preg_match_all($pattern, $outputText, $matches, PREG_PATTERN_ORDER)) {
                $outputText = $this->getErrorMessage($prefix, 'No JIRA issues found') . $outputText;
                continue;
            }
            $issueNumbersWithPrefix = array_unique($matches[1]); // must filter out duplicates
            $issueNumbers = array_map( // remove prefix
                function ($val) use ($prefix) {
                    return str_replace($prefix, '', $val);
                },
                $issueNumbersWithPrefix
            );

            // Form url to JIRA API endpoint. Issue keys are numbers prefixed with board name.
            $url = sprintf(
                '%s?fields=summary&maxResults=%d&jql=%s',
                $this->endpoint,
                count($issueNumbers),
                rawurlencode(
                    "project=${projectName} AND key IN (${boardName}-" . implode(",${boardName}-", $issueNumbers) . ')'
                )
            );

            // Run GET request to JIRA API
            curl_setopt($this->curlHandler, CURLOPT_URL, $url);
            $response =  json_decode(curl_exec($this->curlHandler), true) ?: [];

            // Check if all issues were found
            $issues = $response['issues'] ?? [];
            if (count($issues) !== count($issueNumbers)) {
                $foundIssueKeys = array_map(
                    function ($issue) {
                        return preg_replace('/[^\d]/', '', $issue['key']);
                    },
                    $issues
                );

                $message = 'Could not find the following JIRA issues (could be just one that is wrong) - '
                    . json_encode(array_diff($issueNumbers, $foundIssueKeys));
                $outputText = $this->getErrorMessage($prefix, $message) . $outputText;
                continue;
            }

            // Create replacement strings
            $issueNumberReplacements = [];
            foreach ($issues as $issue) {
                $key = $issue['key'];
                $issueKey = preg_replace('/[^\d]/', '', $key);
                if (!in_array($issueKey, $issueNumbers)) {
                    $outputText = $this->getErrorMessage($prefix, "Issue ${key} not found") . $outputText;
                    continue;
                }

                $issueNumberReplacements[] = '[' . $key . ' ' . ($issue['fields']['summary'] ?? '') . ']';
            }

            // Ensure same order before replacing strings
            sort($issueNumbersWithPrefix);
            sort($issueNumberReplacements);
            $outputText = str_replace($issueNumbersWithPrefix, $issueNumberReplacements, $outputText);
        }

        return $outputText;
    }

    /**
     * Get standardized error message
     *
     * @param string $prefix
     * @param string $message
     * @return string
     */
    protected function getErrorMessage($prefix, $message)
    {
        return sprintf("<Error>%s</Error>\n\n", "Prefix {$prefix}: {$message}");
    }
}
