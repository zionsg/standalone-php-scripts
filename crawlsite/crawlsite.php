<?php
/**
 * Script to crawl website
 *
 * Replace $site with your own url and run the script.
 * Only downlevel links are traversed.
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/crawlsite
 * @since  2013-11-06T19:00+08:00
 */

$site = 'http://example.com/test';

set_time_limit(0);
$start = microtime(true);
$links = crawlSite($site);

printf(
    "<pre>Site: %s\nTime taken: %s seconds\nLinks crawled: %s\n\n%s\n</pre>",
    $site,
    (microtime(true) - $start),
    count($links),
    print_r($links, true)
);

/**
 * Crawl site for links
 *
 * @param  string $site
 * @param  array  $pageExtensions
 * @param  array  $attributes
 * @return array  array('webpage' => array(link1, link2, ...))
 */
function crawlSite($site,
                   $pageExtensions = array('htm', 'html', 'php', 'phtml'),
                   $attributes = array('href', 'src')
) {
    $site = rtrim($site, "\\/");
    $siteParts = parse_url($site);
    $domain = $siteParts['scheme'] . '://' . $siteParts['host'];

    $queue = array($site => $site); // $site is used as key to prevent duplicates
    $processed = array(); // keep tracked of processed links
    $links = array();
    while (!empty($queue)) {
        $url = array_shift($queue);
        $processed[$url] = true;
        $basePath = $url;

        // Only process downlevel links
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
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        if ($extension) {
            if (!in_array($extension, $pageExtensions)) {
                continue;
            }
            $basePath = pathinfo($url, PATHINFO_DIRNAME);
        }

        // Get contents of webpage
        $contents = getUrlContents($url);
        if ($contents === false) {
            continue;
        }

        foreach ($attributes as $attrib) {
            $matches = array();
            if (!preg_match_all('~' . $attrib . '="([^"]+)"~', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[1] as $match) {
                list($link, $offset) = $match;

                if (!preg_match('~^(([^:]+):)?//~', $link)) { // relative url
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
    }

    return $links;
}

/**
 * Download content using CURL - file_get_contents() will not work if "allow_url_fopen" is set to false
 *
 * @param  string      $url
 * @return string|bool Returns false if CURL library not installed
 */
function getUrlContents($url)
{
    if (!function_exists('curl_init')) {
        return false;
    }

    $curlHandler = curl_init(); // initialize a new curl resource
    $options = array(
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_URL => $url, // set the url to post to
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true, // return value instead of output to browser
        CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
        CURLOPT_SSL_VERIFYPEER => false,
    );
    curl_setopt_array($curlHandler, $options);
    $output = curl_exec($curlHandler);  // get content
    curl_close($curlHandler);

    return $output;
}
