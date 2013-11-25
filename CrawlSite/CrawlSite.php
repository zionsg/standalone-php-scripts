<?php
/**
 * Crawl website using downwards traversal only
 *
 * For each webpage crawled, a local copy with renamed links to webpages will be saved.
 *
 * Usage:
 *     $crawler = new CrawlSite();
 *     $links = $crawler('http://example.com/test/');
 *     echo '<pre>' . print_r($links, true) . '</pre>';
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/CrawlSite
 * @since   2013-11-06T19:00+08:00
 */

include 'UrlToAbsolute/url_to_absolute.php';

/**
 * @link http://nadeausoftware.com/articles/2008/05/php_tip_how_convert_relative_url_absolute_url for url_to_absolute
 * @link http://htmlparsing.com/php.html on why DOMDocument is used instead of regular expression
 */
class CrawlSite
{
    /**
     * Web page extensions
     *
     * Webpages with these extensions will be crawled and saved locally.
     *
     * @var array
     */
    protected $pageExtensions = array('htm', 'html', 'php', 'phtml');

    /**
     * Default extension for local copy of webpage
     *
     * @var string
     */
    protected $defaultExtension = 'php';

    /**
     * Path to directory where site resides in - for determining downward links
     *
     * @var string
     */
    protected $siteDir = '';

    /**
     * CURL Handler
     *
     * @var resource
     */
    protected $curlHandler;

    /**
     * Tracking list for processed files
     *
     * @var array
     */
    protected $processed = array();

    /**
     * Queue
     *
     * @var array
     */
    protected $queue = array();

    /**
     * Links
     *
     * @var array
     */
    protected $links = array();

    /**
     * Constructor
     *
     * @throws Exception if CURL library is not installed
     */
    public function __construct()
    {
        if (!function_exists('curl_init')) {
            throw new Exception('CURL library not installed');
        }

        $this->curlHandler = curl_init(); // initialize a new CURL resource
        curl_setopt_array($this->curlHandler, array(
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true, // return value instead of output to browser
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'], // some servers reject requests with no user agent
            CURLOPT_SSL_VERIFYPEER => false, // for HTTPS sites
        ));
    }

    /**
     * Destructor
     *
     * Ensure CURL resource is freed
     */
    public function __destruct()
    {
        curl_close($this->curlHandler);
    }

    /**
     * Crawl site using downwards traversal only
     *
     * For each webpage crawled, a local copy with renamed links to webpages will be saved.
     * The renamed links will be converted to absolute links for easy naming of local copies.
     *
     * Only meta refresh links and <a> href links are crawled.
     *
     * @param  string $site
     * @return array  array('webpage' => array(link1, link2, ...))
     */
    function __invoke($site)
    {
        $dom = new DOMDocument();

        // Get path to directory where $site resides in - for determining downward links
        // parse_url() used else http://example.com will give ".com" extension
        if (pathinfo(parse_url($site, PHP_URL_PATH), PATHINFO_EXTENSION)) {
            $this->siteDir = pathinfo($site, PATHINFO_DIRNAME) . '/';
        } else {
            $site = rtrim($site, "\\/") . '/';
            $this->siteDir = $site;
        }

        // Clear any previous work
        clearstatcache();
        $this->queue = array($site => $site); // $site is used as key to prevent duplicates
        $this->processed = array();
        $this->links = array();

        while (!empty($this->queue)) {
            $url = array_shift($this->queue);
            $this->processed[$url] = true;

            // Only process downward links
            // "/" is appended to prevent http://example.com/test from being matched in http://example.com/test123
            // and to ensure http://example.com will match http://example.com/
            if (stripos($url . '/', $this->siteDir) === false) {
                continue;
            }

            // Skip urls with # in them else infinite loop
            if (stripos($url, '#') !== false) {
                continue;
            }

            // Only process contents of webpages
            // parse_url() is used else "http://example.com/test.php?id=2" will not match
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if ($extension && !in_array($extension, $this->pageExtensions)) {
                continue;
            }

            // Get contents of webpage using CURL
            curl_setopt($this->curlHandler, CURLOPT_URL, $url);
            $contents = curl_exec($this->curlHandler);
            if ($contents === false) {
                continue;
            }

            // Parse HTML - @ suppresses any warnings that loadHTML might throw because of invalid HTML in the page
            @$dom->loadHTML($contents);

            // Need to check for meta refresh tags else crawling may stop at index page
            foreach ($dom->getElementsByTagName('meta') as $element) {
                if ($element->getAttribute('http-equiv') != 'refresh') {
                    continue;
                }
                $matches = array();
                if (!preg_match('~^(.*)url=(.*)$~i', $element->getAttribute('content'), $matches)) {
                    continue;
                }
                $link = $this->processLink($matches[2], $url);
                $element->setAttribute('content', $matches[1] . $link);
            }

            // <a> and <area> tags whose href attributes usually point to webpages. <base> and <link> excluded
            foreach (array('a', 'area') as $tag) {
                foreach ($dom->getElementsByTagName($tag) as $element) {
                    $link = $this->processLink($element->getAttribute('href'), $url);
                    $element->setAttribute('href', $link);
                }
            }

            // Save local copy - error may occur if directory nesting is too deep or if path is too long
            // Unlike urls, %20 and spaces not the same for files, hence %20 must be converted back to spaces
            $renamedUrl = $this->renameUrl($url);
            $filename   = preg_replace(
                '~^(.*:[\\/]+)~',
                str_replace("\\", '/', getcwd()) . '/',
                $renamedUrl
            );
            $filename = str_replace('%20', ' ', $filename);
            $dir = dirname($filename);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $dom->saveHTMLFile($filename);

        } // end while

        return $this->links;
    } // end function __invoke

    /**
     * Process link including queueing and renaming it
     *
     * Only webpages will be queued and renamed to absolute paths
     *
     * @param  string $link
     * @param  string $baseUrl
     * @return string Renamed link
     */
    protected function processLink($link, $baseUrl)
    {
        $extension = pathinfo(parse_url($link, PHP_URL_PATH), PATHINFO_EXTENSION);

        if (trim($link) == '') { // Empty link
           $renamedLink = $link;
        } elseif (substr($link, 0, 11) == 'javascript:') { // Javascript links
            $renamedLink = $link;
        } elseif ($extension && !in_array($extension, $this->pageExtensions)) { // Non-webpages such as .css, .js, .jpg
            $renamedLink = $link;
        } else { // Webpages
            // url_to_absolute cannot handle spaces in paths, hence replacing with %20
            $link = rawurldecode(url_to_absolute($baseUrl, str_replace(' ', '%20', $link)));

            if (stripos($link, $this->siteDir) !== false) {
                $renamedLink  = $this->renameUrl($link);
            } else {
                $renamedLink = $link;
            }

            if (!isset($this->processed[$link])) {
                $this->queue[$link] = $link;
            }
        }

        $this->links[$baseUrl][] = $link;
        return $renamedLink;
    }

    /**
     * Build url from parse_url() return values
     *
     * Basic alternative to http_build_url() which requires PECL extension.
     * Generic syntax from RFC 3986 — STD 66, chapter 3:
     *   scheme://username:password@domain:port/path?query_string#fragment_id
     *
     * @see    http://www.php.net/manual/en/function.parse-url.php#106731 for source
     * @param  array $parts Url components as per return values for parse_url()
     * @return string
     */
    protected function buildUrl(array $parts)
    {
        $scheme   = isset($parts['scheme'])
                  ? $parts['scheme'] . ':' . (('mailto' == strtolower($parts['scheme'])) ? '' : '//')
                  : '';
        $host     = isset($parts['host']) ? $parts['host'] : '';
        $port     = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user     = isset($parts['user']) ? $parts['user'] : '';
        $pass     = isset($parts['pass']) ? ':' . $parts['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parts['path']) ? $parts['path'] : '';
        $query    = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Renames url with query string to filesystem friendly url
     *
     * @example http://example.com/test => http://example.com/test/index.php
     * @example http://example.com/stylesheet.css => http://example.com/stylesheet.css
     * @example http://example.com/test.php?id=1&category=2 => http://example.com/test_id-1_category22.php
     *          If the file test.php exists, Windows does not allow the creation of a folder named "test.php", hence
     *          not renamed to http://example.com/test.php/id/1/category/2/index.php.
     *          New file must stay in same folder as old file to ensure relative images/scripts/stylesheets will work.
     * @param   string $url
     * @return  string
     */
    protected function renameUrl($url)
    {
        $parts = parse_url($url);
        $extension = pathinfo($parts['path'], PATHINFO_EXTENSION);

        // For non-webpages such as .css, .js, .jpg
        if ($extension && !in_array($extension, $this->pageExtensions)) {
            return $url;
        }

        // If no extension, eg. http://example.com/test or http:://example.com/test/?id=5, default to index.php
        if (!$extension) {
            $extension = $this->defaultExtension;
            $parts['path'] = rtrim($parts['path'], "\\/") . "/index.{$extension}";
        }

        if (isset($parts['query'])) {
            $parts['query'] = str_replace(array('=', '&'), array('-', '_'), $parts['query']);
            $parts['path'] = substr($parts['path'], 0, -(1 + strlen($extension))) . "_{$parts['query']}.{$extension}";
            unset($parts['query']);
        }

        return $this->buildUrl($parts);
    }
}
