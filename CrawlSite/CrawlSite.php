<?php
/**
 * Script to crawl website for links using downwards traversal only
 *
 * Usage:
 *     $crawler = new CrawlSite();
 *     $links = $crawler('http://example.com/test');
 *     echo '<pre>' . print_r($links, true) . '</pre>';
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/CrawlSite
 * @since   2013-11-06T19:00+08:00
 */
class CrawlSite
{
    /**
     * Web page extensions
     *
     * @var array
     */
    protected $pageExtensions = array('htm', 'html', 'php', 'phtml');

    /**
     * CURL Handler
     *
     * @var resource
     */
    protected $curlHandler;

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
     * Crawl site for links using downwards traversal only
     *
     * @param  string $site
     * @return array  array('webpage' => array(link1, link2, ...))
     */
    function __invoke($site)
    {
        // Types of links - header() and meta refresh tags need to be processed else may stop at index page
        $pattern = '~'
                 . 'header\s*\([\'"]Location:\s*([^\'"]+)[\'"]\);'
                 . '|meta.+refresh.+content=\s*[\'"].*url=([^\'"]+)[\'"]'
                 . '|href=[\'"]([^\'"]+)[\'"]'
                 . '|src=[\'"]([^\'"]+)[\'"]'
                 . '~i';

        $site = rtrim(trim($site), "\\/");
        $siteParts = parse_url($site);
        $domain = $siteParts['scheme'] . '://' . $siteParts['host'];

        $queue = array($site => $site); // $site is used as key to prevent duplicates
        $processed = array(); // keep tracked of processed links
        $links = array();
        while (!empty($queue)) {
            $url = array_shift($queue);
            $processed[$url] = true;
            $basePath = $url;

            // Only process downward links
            // "/" is appended to prevent http://example.com/test from being matched in http://example.com/test123
            // and to ensure http://example.com will match http://example.com (hence appended to $url also)
            if (stripos($url . '/', $site . '/') === false) {
                continue;
            }

            // Skip urls with # in them else infinite loop
            if (stripos($url, '#') !== false) {
                continue;
            }

            // Only process contents of webpages
            // parse_url() is used else "http://example.com/test.php?id=2" will not match
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if ($extension) {
                if (!in_array($extension, $this->pageExtensions)) {
                    continue;
                }
                $basePath = pathinfo($url, PATHINFO_DIRNAME);
            }

            // Get contents of webpage using CURL
            curl_setopt($this->curlHandler, CURLOPT_URL, $url);
            $contents = curl_exec($this->curlHandler);
            if ($contents === false) {
                continue;
            }

            // Find links
            $matches = array();
            if (!preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $matchCnt = count($matches);
            for ($i = 1; $i < $matchCnt; $i++) {
                foreach ($matches[$i] as $match) {
                    if (!is_array($match) || !$match[0]) {
                        continue;
                    }
                    list($link, $offset) = $match;

                    if (!preg_match('~^(([^:]+):)?//~', $link)) { // relative url
                        if ('javascript:' == substr($link, 0, 11)) { // do not process javascript calls
                            continue;
                        }

                        $firstChar = substr($link, 0, 1);
                        $firstTwoChars = substr($link, 0, 2);

                        if ('/' == $firstChar) {
                            $link = $domain . $link;
                        } elseif ('..' == $firstTwoChars) {
                            $link = $basePath . '/' . $link;
                        } elseif ('.' == $firstChar) {
                            $link = $basePath . substr($link, 1);
                        } else {
                            $link = $basePath . '/' . $link; // subfolder under site
                        }
                    }

                    if (!isset($processed[$link])) {
                        $queue[$link] = $link;
                    }
                    $links[$url][] = $link;
                }
            }
        } // end while

        return $links;
    } // end function __invoke
}
