<?php
/**
 * Convert issue numbers in text to JIRA issue keys and titles
 *
 * Notes:
 *   - Numbers starting with "j" will be treated as JIRA issue numbers. A prefix is required to
 *     prevent confusion with other numbers used for currency and time.
 *   - The text "Status of j123: Done" converts to "Status of [MYBOARD-123 My Issue]: Done".
 *
 * Usage (see __construct() docblock on username and password):
 *     $app = new JiraTitles($username, $password);
 *     $text = file_get_contents('daily-standup-minutes.txt');
 *     echo $app->convert($text, 'MYPROJECT', 'MYBOARD');
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
     * @param string $inputText
     * @param string $projectName JIRA project
     * @param string $boardName JIRA board
     * @return string Text with issue numbers converted
     */
    public function convert($inputText, $projectName, $boardName)
    {
        // Find JIRA issue numbers
        $pattern = '/(j\d+)/';
        $matches = [];
        if (!preg_match_all($pattern, $inputText, $matches, PREG_PATTERN_ORDER)) {
            return 'No JIRA issue numbers found.';
        }
        $issueNumbersWithPrefix = array_unique($matches[1]); // must filter out duplicates
        $issueNumbers = array_map( // remove "j"
            function ($val) {
                return str_replace('j', '', $val);
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

            return 'Could not find the following JIRA issues: '
                . json_encode(array_diff($issueNumbers, $foundIssueKeys)) . "\n";
        }

        // Create replacement strings
        $issueNumberReplacements = [];
        foreach ($issues as $issue) {
            $key = $issue['key'];
            $issueKey = preg_replace('/[^\d]/', '', $key);
            if (!in_array($issueKey, $issueNumbers)) {
                echo "Issue ${key} not found.\n";
            }

            $issueNumberReplacements[] = '[' . $key . ' ' . ($issue['fields']['summary'] ?? '') . ']';
        }

        // Ensure same order before replacing strings
        sort($issueNumbersWithPrefix);
        sort($issueNumberReplacements);
        return str_replace($issueNumbersWithPrefix, $issueNumberReplacements, $inputText);
    }
}
