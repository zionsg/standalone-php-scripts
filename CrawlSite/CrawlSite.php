<?php
/**
 * Crawl website using downwards traversal only
 *
 * This can be used to convert a dynamic PHP site to a static site as local copies of
 * webpages are saved with dynamic links changed to static ones, eg: test.php?id=3 => test_id-3.php
 * Assets such as images, .css and .js are not saved.
 *
 * After conversion, the following will need to be done manually:
 *   1) Remove XML tags from PHP files else they may not parse properly if short_tags are enabled
 *      Eg: <?xml version="1.0" encoding="utf-8"?>
 *   2) Convert the files to UTF-8 encoding with BOM. Bulk conversion can be done via the PythonScript plugin
 *      in Notepad++. See https://github.com/zionsg/standalone-php-scripts/tree/master/CrawlSite/npp_convert_utf8.py
 *
 * Usage:
 *     set_time_limit(0); // can take more than 30 mins
 *     $crawler = new CrawlSite();
 *     $links = $crawler('http://example.com/test/');
 *     echo '<pre>' . print_r($links, true) . '</pre>';
 *
 * Helper functions:
 *     addLinkType($tag, $attribute, $pattern) can be used to add additional link types to check. 
 *     getLinkTypes() returns the link types that will be checked.
 * 
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/CrawlSite
 * @since  2013-11-06T19:00+08:00
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
     * Link types to check (tag, attribute, pattern)
     *
     * Default link types are <a>, <area> & <iframe> tags whose href/src attributes usually point to webpages.
     * <base> and <link> are excluded.
     *
     * The pattern, if not empty, is used as a regular expression to extract the actual link
     * from the attribute, eg: <a onclick="alert(jQuery.get('get.php?page=content').readyState);">load content</a>.
     * The pattern must contain 3 named groups to facilitate search and replace: before, link, after.
     * Eg: ~(?P<before>jQuery\.get\(')(?P<link>[^']+)(?P<after>'\);)~i
     *
     * @example <htmlTag1> => array(<tagAttribute1> => array(<attributePattern1>, <attributePattern2>))
     * @var     array
     */
    protected $linkTypes = array(
        'a' => array(
            'href' => array(''), // '' as a pattern will take the entire value of the attribute and skip preg_match()
        ),
        'area' => array(
            'href' => array(''),
        ),
        'iframe' => array(
            'src' => array(''),
        ),
        'script' => array(
            // '' as a tag attribute will check the textContent property of the DOMElement
            // (in this case the contents of <script>) for each regex pattern RECURSIVELY using preg_match_all()
            // as there may be more than 1 occurrence
            '' => array('~(?P<before>location\.href=([\'"]))(?P<link>.+)(?P<after>\2)~'),
        ),
    );

    /**
     * Path to directory (without www) where site resides in - for determining downward links
     *
     * @example http://example.com/
     * @var string
     */
    protected $siteDir = '';

    /**
     * Path to directory (with www) where site resides in - for determining downward links
     *
     * @example http://www.example.com/
     * @var string
     */
    protected $siteDirWww = '';

    /**
     * Alias path to directory (without www) where site resides in - for determining downward links
     *
     * @example http://example.com/
     * @var string
     */
    protected $siteDirAlias = '';

    /**
     * Alias path to directory (with www) where site resides in - for determining downward links
     *
     * @example http://www.example.com/
     * @var string
     */
    protected $siteDirAliasWww = '';

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
            CURLOPT_HEADER => true, // include headers in return value for inspection
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'], // some servers reject requests with no user agent
            CURLOPT_SSL_VERIFYPEER => false, // for HTTPS sites
            CURLOPT_FRESH_CONNECT => true, // prevent caching problems
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
     * Links in <meta> refresh tags are checked as well in addition to $linksToCheck.
     *
     * @param  string $site
     * @param  string $siteAlias Sometimes it may not be possible to crawl a live site as its web server
     *                           may reject/throttle the many incoming page requests. If the user has
     *                           a copy of the site on localhost, this script can be run as
     *                           __invoke('http://127.0.0.1:1234/site2000', 'http://example.com/site2000')
     *                           and all links under $siteAlias will be renamed to reflect $site
     * @return array  array('webpage' => array(link1, link2, ...))
     */
    function __invoke($site, $siteAlias = null)
    {
        // Clear any previous work and init variables
        clearstatcache();
        $this->computeSiteDir($site);
        if ($siteAlias) {
            $this->computeSiteDir($siteAlias, 'siteDirAlias', 'siteDirAliasWww');
        }
        $this->queue = array($site => $site); // $site is used as key to prevent duplicates
        $this->processed = array();
        $this->links = array();

        while (!empty($this->queue)) {
            $url = array_shift($this->queue);
            $this->processed[$url] = true;

            // Only process downward links
            if (!$this->isDownwardLink($url)) {
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
            } elseif (!$extension) {
                // Ensure directories such as http://example.com/test is coded as http://example.com/test/
                // url_to_absolute('http://example.com/test', 'sample.php') => http://example.com/sample.php
                // url_to_absolute('http://example.com/test/', 'sample.php') => http://example.com/test/sample.php
                $url = rtrim($url, "\\/") . '/';
            }

            // Rename url if it contains site alias - at this point it is certain that this is a downward link
            if ($this->siteDirAlias) {
                $url = str_replace($this->siteDirAlias, $this->siteDir, $url);
                $url = str_replace($this->siteDirAliasWww, $this->siteDirWww, $url);
            }

            // Get contents of webpage using CURL - headers are prepended as well
            curl_setopt($this->curlHandler, CURLOPT_URL, $url);
            $contents = curl_exec($this->curlHandler);
            if ($contents === false) {
                continue;
            }
            // CURLINFO_EFFECTIVE_URL may not always reflect redirects, hence checking of headers
            $effectiveUrl = curl_getinfo($this->curlHandler, CURLINFO_EFFECTIVE_URL);
            $headerSize = curl_getinfo($this->curlHandler, CURLINFO_HEADER_SIZE);
            $headers = explode("\n", substr($contents, 0, $headerSize));
            $contents = substr($contents, $headerSize);
            foreach ($headers as $header) {
                $headerArray = explode(':', $header, 2); // limit to 2 tokens cos of http://
                if (false === $headerArray || count($headerArray) != 2) {
                    continue;
                }

                list($key, $value) = array_map('trim', $headerArray);
                if ('Location' == $key) {
                    $effectiveUrl = $value;
                    break;
                } elseif ('Refresh' == $key) {
                    $effectiveUrl = trim(substr($value, stripos($value, 'url=') + 4));
                    break;
                }
            }
            // Replace contents with meta tag if last effective url of webpage is not a downward link
            if (!$this->isDownwardLink($effectiveUrl)) {
                $contents = sprintf(
                    '<meta http-equiv="refresh" content="0;url=%s">',
                    $effectiveUrl
                );
                $this->processed[$effectiveUrl] = true; // mark as processed
            }

            // Parse HTML - @ suppresses any warnings that loadHTML might throw because of invalid HTML in the page
            $dom = new DOMDocument();
            @$dom->loadHTML($contents);
            $dom->encoding = 'UTF-8';

            // Need to check for meta refresh tags else crawling may stop at index page
            foreach ($dom->getElementsByTagName('meta') as $element) {
                if (strcasecmp($element->getAttribute('http-equiv'), 'refresh') != 0) {
                    continue;
                }
                $matches = array();
                if (!preg_match('~^(.*url=)(.*)$~i', $element->getAttribute('content'), $matches)) {
                    continue;
                }
                $link = $this->processLink($matches[2], $url);
                $element->setAttribute('content', $matches[1] . $link);
            }

            // Link types to check
            foreach ($this->linkTypes as $linkTag => $linkAttributes) {
                foreach ($dom->getElementsByTagName($linkTag) as $element) {
                    foreach ($linkAttributes as $linkAttrib => $linkPatterns) {
                        if ($linkAttrib) {
                            $value = $element->getAttribute($linkAttrib);

                            foreach ($linkPatterns as $linkPattern) {
                                if (!$linkPattern) {
                                    $link = $this->processLink($value, $url);
                                    $element->setAttribute($linkAttrib, $link);
                                } else {
                                    // Pattern has 3 named groups: '~(before)(link)(after)~'
                                    $matches = array();
                                    if (!preg_match($linkPattern, $value, $matches)) {
                                        continue;
                                    }
                                    $link = $this->processLink($matches['link'], $url);
                                    $element->setAttribute($linkAttrib, preg_replace(
                                        $linkPattern,
                                        $matches['before'] . $link . $matches['after'],
                                        $value
                                    ));
                                }
                            }
                        } else { // check node contents and replace $element->nodeValue (textContent is readonly)
                            $value = $element->textContent;
                            foreach ($linkPatterns as $linkPattern) {
                                if (!$linkPattern) {
                                    $link = $this->processLink($value, $url);
                                    $element->nodeValue = $link;
                                } else {
                                    // Pattern has 3 named groups: '~(before)(link)(after)~'
                                    $matches = array();
                                    if (!preg_match_all($linkPattern, $value, $matches, PREG_OFFSET_CAPTURE)) {
                                        continue;
                                    }

                                    // String together new content
                                    $newContent = '';
                                    $matchCnt = count($matches[0]);
                                    $prevOffset = 0;
                                    for ($i = 0; $i < $matchCnt; $i++) {
                                        $link = $this->processLink($matches['link'][$i][0], $url);
                                        // Add preceding content, <before>, link and <after>
                                        $newContent .= substr($value, $prevOffset, $matches['before'][$i][1] - $prevOffset)
                                                     . $matches['before'][$i][0] . $link . $matches['after'][$i][0];
                                        $prevOffset = $matches['after'][$i][1] + strlen($matches['after'][$i][0]);
                                    }
                                    $newContent .= substr($value, $prevOffset); // append rest of content
                                    $element->nodeValue = $newContent;
                                }
                            }
                        }
                    }
                }
            }

            // Save local copy - error may occur if directory nesting is too deep or if path is too long
            // Unlike urls, %20 and spaces not the same for files, hence %20 must be converted back to spaces
            $renamedUrl = $this->renameUrl($url);
            $filename   = preg_replace(
                array('~^(.*:[\\/]{2})~', '~:([0-9]{1,})~'),
                array(str_replace("\\", '/', getcwd()) . '/', '_\\1'),
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
     * Add link type to check
     *
     * @example @see $linkTypes
     * @param   string $tag
     * @param   string $attribute
     * @param   string $pattern   Optional regex pattern
     * @return  void
     */
    public function addLinkType($tag, $attribute, $pattern = '')
    {
        $this->linkTypes[$tag][$attribute][] = $pattern;
    }

    /**
     * Get link types that will be checked
     *
     * @return array
     */
    public function getLinkTypes()
    {
        return $this->linkTypes;
    }

    /**
     * Compute site directories (without and with www)
     *
     * @param  string $site
     * @param  string $siteDirVar    Name of property to store site dir without www
     * @param  string $siteDirWwwVar Name of property to store site dir with www
     * @return void
     */
    protected function computeSiteDir($site, $siteDirVar = 'siteDir', $siteDirWwwVar = 'siteDirWww')
    {
        // Get path to directory where $site resides in - for determining downward links
        // parse_url() used else http://example.com will give ".com" extension
        if (pathinfo(parse_url($site, PHP_URL_PATH), PATHINFO_EXTENSION)) { // eg. http://example.com/index.php
            $siteDir = str_replace('://www.', '://', pathinfo($site, PATHINFO_DIRNAME)) . '/';
            $this->{$siteDirVar} = $siteDir;
            $this->{$siteDirWwwVar} = str_replace('://', '://www.', $siteDir);
        } else { // eg. http://example.com/
            $site = rtrim($site, "\\/") . '/';
            $siteDir = str_replace('://www.', '://', $site);
            $this->{$siteDirVar} = $siteDir;
            $this->{$siteDirWwwVar} = str_replace('://', '://www.', $siteDir);
        }
    }

    /**
     * Check if a url is a downward link
     *
     * If the url is a downward link of $siteDirAlias or $siteDirAliasWww,
     * the url is modified to use $siteDir (without www).
     *
     * @param  string $url Passed in by reference
     * @return bool
     */
    protected function isDownwardLink(&$url)
    {
        // "/" is appended to prevent http://example.com/test from being matched in http://example.com/test123
        // and to ensure http://example.com will match http://example.com/
        $urlWithSlash = $url . '/';
        if (   (stripos($urlWithSlash, $this->siteDir) !== false)
            || (stripos($urlWithSlash, $this->siteDirWww) !== false)
        ) {
            return true;
        }

        // Check site dir alias
        if (!$this->siteDirAlias) {
            return false;
        }

        if (stripos($urlWithSlash, $this->siteDirAlias) !== false) {
            $url = str_replace($this->siteDirAlias, $this->siteDir, $url);
            return true;
        }

        if (stripos($urlWithSlash, $this->siteDirAliasWww) !== false) {
            $url = str_replace($this->siteDirAliasWww, $this->siteDir, $url);
            return true;
        }

        return false;
    }

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

            if ($this->isDownwardLink($link)) {
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
     * @example http://example.com/test.php?id=1&category=2 => http://example.com/test_id-1_category-2.php
     *          If the file test.php exists, Windows does not allow the creation of a folder named "test.php", hence
     *          not renamed to http://example.com/test.php/id/1/category/2/index.php.
     *          New file must stay in same folder as old file to ensure relative images/scripts/stylesheets will work.
     * @param   string $url
     * @return  string
     */
    protected function renameUrl($url)
    {
        $parts = parse_url($url);
        if (false === $parts || !isset($parts['path'])) {
            return $url;
        }
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
            $parts['query'] = str_replace(
                array(' ', '"', '\\', '/', ':', '?', '*', '<', '>', '|'),
                '',
                $parts['query']
            );
            $parts['path'] = substr($parts['path'], 0, -(1 + strlen($extension))) . "_{$parts['query']}.{$extension}";
            unset($parts['query']);
        }

        return $this->buildUrl($parts);
    }
}
