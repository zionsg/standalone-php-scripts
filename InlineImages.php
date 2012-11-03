<?php
/**
 * Get url content and convert images to inline images
 *
 * Usage:
 *     $instance = new InlineImages();
 *     echo $instance->processUrl('http://www.example.com');
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    https://github.com/zionsg/standalone-php-scripts/
 * @since   2012-11-03T11:45+08:00
 */
class InlineImages
{
    protected $curlHandler;

    protected $imageTypes = array(
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
    );

    protected $userAgent =
        'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4';

    /**
     * Constructor
     *
     * Initialize CURL handler
     * Set user agent for CURL to mimic a browser
     *
     * @param  string $userAgent OPTIONAL. User agent used to mimic browser
     * @throws Exception This is thrown if the CURL library is not installed
     */
    public function __construct($userAgent = null)
    {
        // Make sure curl is installed
        if (!function_exists('curl_init')) {
            throw new Exception('CURL library not installed!');
        }

        $this->userAgent = $userAgent ?: $this->userAgent;
        $this->curlHandler = curl_init(); // initialize a new curl resource
        curl_setopt($this->curlHandler, CURLOPT_HEADER, 0); // don't get headers, just the content
        curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, 1); // return value instead of output to browser
        curl_setopt($this->curlHandler, CURLOPT_USERAGENT, $this->userAgent); // use a user agent to mimic a browser
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
     * Get url content and replace images with inline images
     *
     * This works only with images that have absolute paths for their src
     *
     * @param  string $url Url to read
     * @return string
     */
    public function processUrl($url)
    {
        $urlContent = $this->getUrlContent($url);

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
            list($imageUrl, $offset) = $match;
            $imageUrl = str_replace(array('\'', '"'), '', $imageUrl); // strip quotes

            $extension = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
            $imageType = isset($this->imageTypes[$extension])
                       ? $this->imageTypes[$extension]
                       : $this->imageTypes['jpg'];

            $inlineImage = sprintf(
                '"data:%s;base64,%s"',
                $imageType,
                base64_encode($this->getUrlContent($imageUrl))
            );

            // 2nd parameter in substr() is no. of chars to take, NOT ending position
            $result .= substr($urlContent, $prevOffset, ($offset - $prevOffset))
                     . $inlineImage;
            $prevOffset = $offset + strlen($imageUrl);
        }
        $result .= substr($urlContent, $prevOffset); // rest of url content

        return $result;
    } // end function processUrl

    /*
     * Get url content
     *
     * @param  string $url Url to read
     * @return string
     */
    public function getUrlContent($url)
    {
        curl_setopt($this->curlHandler, CURLOPT_URL, $url); // set the url to fetch
        return curl_exec($this->curlHandler); // get content
    }

} // end class