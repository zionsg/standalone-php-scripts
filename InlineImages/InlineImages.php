<?php
/**
 * Get url content and convert images to inline images
 *
 * Usage:
 *     $instance = new InlineImages();
 *     echo $instance('http://www.example.com');
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/InlineImages
 * @since   2012-11-03T11:45+08:00
 */
class InlineImages
{
    /**
     * CURL handler
     * @var resource
     */
    protected $curlHandler;

    /**
     * Image types
     * @var array
     */
    protected $imageTypes = array(
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
    );

    /**
     * Default user agent used by CURL to mimic browser
     * @var string
     */
    protected $userAgent =
        'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4';

    /**
     * Constructor
     *
     * Initialize CURL handler
     *
     * @throws Exception This is thrown if the CURL library is not installed
     */
    public function __construct()
    {
        // Make sure curl is installed
        if (!function_exists('curl_init')) {
            throw new Exception('CURL library not installed!');
        }

        $this->curlHandler = curl_init(); // initialize a new curl resource
        curl_setopt($this->curlHandler, CURLOPT_HEADER, 0); // don't get headers, just the content
        curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, 1); // return value instead of output to browser
    }

    /**
     * Destructor
     *
     * Close CURL handler
     */
    public function __destruct()
    {
        if (!empty($this->curlHandler)) {
            curl_close($this->curlHandler);
        }
    }

    /**
     * __invoke
     *
     * Get url content and replace images with inline images
     * This works only with images that have absolute paths for their src
     *
     * @param  string $url Url to read
     * @param  string $useragent OPTIONAL. User agent for CURL to mimic browser
     * @return string
     */
    public function __invoke($url, $userAgent = null)
    {
        $urlContent = $this->getUrlContent($url, $userAgent);

        // Quotes are included in match as src may not be enclosed in quotes
        // They will be stripped from the individual matches later on
        // Note that the pattern between 'img' and 'src' is not .* as that
        // will miss matches where there is more than 1 <img> near each other
        $pattern = '~<img[^>]*src=([\'"]*[^\'" ]+[\'"]*)[^>]*>~i';
        $matches = array();
        if (!preg_match_all($pattern, $urlContent, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        };

        $result = '';
        $prevOffset = 0;
        foreach ($matches[1] as $match) {
            list($imageUrlWithQuotes, $offset) = $match;
            $imageUrl = str_replace(array('\'', '"'), '', $imageUrlWithQuotes); // strip quotes

            $extension = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
            $imageType = isset($this->imageTypes[$extension])
                       ? $this->imageTypes[$extension]
                       : $this->imageTypes['jpg'];

            $inlineImage = sprintf(
                '"data:%s;base64,%s"',
                $imageType,
                base64_encode($this->getUrlContent($imageUrl, $userAgent))
            );

            // 2nd parameter in substr() is no. of chars to take, NOT ending position
            $result .= substr($urlContent, $prevOffset, ($offset - $prevOffset))
                     . $inlineImage;
            $prevOffset = $offset + strlen($imageUrlWithQuotes); // must include the quotes
        }
        $result .= substr($urlContent, $prevOffset); // rest of url content

        return $result;
    } // end function processUrl

    /*
     * Get url content
     *
     * @param  string $url Url to read
     * @param  string $useragent OPTIONAL. User agent for CURL to mimic browser
     * @return string
     */
    public function getUrlContent($url, $userAgent = null)
    {
        // Use a user agent to mimic a browser
        curl_setopt(
            $this->curlHandler,
            CURLOPT_USERAGENT,
            ($userAgent ?: $this->userAgent)
        );
        curl_setopt($this->curlHandler, CURLOPT_URL, $url); // set the url to fetch
        return curl_exec($this->curlHandler); // get content
    }

}
