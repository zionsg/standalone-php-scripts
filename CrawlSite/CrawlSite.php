<?php
/**
 * Crawl website for links using downwards traversal only
 *
 * For each webpage crawled, a local copy with renamed links will be saved.
 *
 * Usage:
 *     $crawler = new CrawlSite();
 *     $links = $crawler('http://example.com/test/');
 *     echo '<pre>' . print_r($links, true) . '</pre>';
 *
 * @see     http://nadeausoftware.com/articles/2008/05/php_tip_how_convert_relative_url_absolute_url for url_to_absolute
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/CrawlSite
 * @since   2013-11-06T19:00+08:00
 */

include 'UrlToAbsolute/url_to_absolute.php';

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
     * For each webpage crawled, a local copy with renamed links will be saved.
     *
     * @param  string $site
     * @return array  array('webpage' => array(link1, link2, ...))
     */
    function __invoke($site)
    {
        // Regex pattern for link types - meta refresh tags need to be processed else may stop at index page
        // Need to check whitespace in front of href/src else Javascript vars such as x.src or x.oSrc will be captured
        // Backreferences to match single/double quotes not used as they end up capturing more than the link
        $pattern = '~'
                 . '<[\s]*meta.+refresh.+content=".*url=([^"]+)"'    // double quotes
                 . '|<[\s]*meta.+refresh.+content=\'.*url=([^\']+)\'' // single quotes
                 . '|[\s]href="([^"]+)"'            // for links with double quotes
                 . '|[\s]href=\'([^\']+)\''         // for links with single quotes
                 . '|[\s]href=([^\'"][^\s]+)[^>]*>' // for links without quotes
                 . '|[\s]src="([^"]+)"'             // for links with double quotes
                 . '|[\s]src=\'([^\']+)\''          // for links with single quotes
                 . '|[\s]src=([^\'"][^\s]+)[^>]*>'  // for links without quotes
                 . '~i';

        // Get path to directory when $site resides in - for determining downward links
        // parse_url() used else http://example.com will give ".com" extension
        if (pathinfo(parse_url($site, PHP_URL_PATH), PATHINFO_EXTENSION)) {
            $siteDir = pathinfo($site, PATHINFO_DIRNAME) . '/';
        } else {
            $site = rtrim($site, "\\/") . '/';
            $siteDir = $site;
        }

        clearstatcache();
        $queue = array($site => $site); // $site is used as key to prevent duplicates
        $processed = array(); // keep tracked of processed links
        $links = array();
        while (!empty($queue)) {
            $url = array_shift($queue);
            $processed[$url] = true;

            // Only process downward links
            // "/" is appended to prevent http://example.com/test from being matched in http://example.com/test123
            // and to ensure http://example.com will match http://example.com/
            if (stripos($url . '/', $siteDir) === false) {
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

            // Find links
            $matches = array();
            if (!preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            // Filter out empty matches and order according to offset
            // Even capturing groups for quote marks must be included else position offsets will be affected
            $orderedMatches = array();
            $matchCnt = count($matches);
            for ($i = 1; $i < $matchCnt; $i++) {
                foreach ($matches[$i] as $match) {
                    if (!is_array($match) || !$match[0]) {
                        continue;
                    }
                    $orderedMatches[$match[1]] = $match[0];
                }
            }
            ksort($orderedMatches);

            // Find and rename downward links in page contents
            $prevOffset = 0;
            $newContents = '';
            foreach ($orderedMatches as $offset => $link) {
                // url_to_absolute does not handle files with spaces in their names, hence replace with %20
                // $link is not modified as it will affect the position offsets
                $isJavascript = (substr($link, 0, 11) == 'javascript:');
                if ($isJavascript) {
                    $absoluteLink = $link;
                } else {
                    $absoluteLink = str_replace(
                        array('%3F', '%3D'),
                        array('?', '='),
                        url_to_absolute($url, str_replace(' ', '%20', $link))
                    );
                }
                $linkLen = strlen($link);

                if (stripos($absoluteLink, $siteDir) !== false) {
                    $renamedLink  = $this->renameUrl($absoluteLink);
                } else {
                    $renamedLink = $absoluteLink;
                }
                $newContents .= substr($contents, $prevOffset, $offset - $prevOffset) . $renamedLink;
                $prevOffset   = $offset + $linkLen;

                if (!isset($processed[$absoluteLink]) && !$isJavascript) {
                    $queue[$absoluteLink] = $absoluteLink;
                }
                $links[$url][] = $absoluteLink;
            }

            // Save local copy - error may occur if directory nesting is too deep or if path is too long
            $newContents .= substr($contents, $prevOffset);
            $renamedUrl   = $this->renameUrl($url);
            $filename     = preg_replace(
                '~^(.*:[\\/]+)~',
                str_replace("\\", '/', getcwd()) . '/',
                $renamedUrl
            );
            $dir = dirname($filename);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($filename, $newContents);

        } // end while

        return $links;
    } // end function __invoke

    /**
     * Renames url with query string to filesystem friendly url
     *
     * @example http://example.com/test => http://example.com/test/index.php
     * @example http://example.com/stylesheet.css => http://example.com/stylesheet.css
     * @example http://example.com/test.php?id=1&category=2 => http://example.com/test.php-id-1-category-2.php
     *          If the file test.php exists, Windows does not allow the creation of a folder named "test.php", hence
     *          not renamed to http://example.com/test.php/id/1/category/2/index.php.
     *          New file must stay in same folder as old file to ensure relative images/scripts/stylesheets will work.
     * @param   string $url
     * @return  string
     */
    protected function renameUrl($url)
    {
        // For non-webpages such as .css, .js, .jpg
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if ($extension && !in_array($extension, $this->pageExtensions)) {
            return $url;
        }

        // For webpages
        $newUrl = str_replace(array('?', '%3F', '=', '%3D'), '-', rtrim(trim($url), "\\/"));
        if ($extension) {
            // Check double extension when there is no query params or fragment, eg. http://example.com/test.php.php
            if (substr($newUrl, -(1 + strlen($extension))) != '.' . $extension) {
                $newUrl .= '.' . $extension;
            }
        } else {
           $newUrl .= '/index.' . $this->defaultExtension;
        }

        return $newUrl;
    }
}
