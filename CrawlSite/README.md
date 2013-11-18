### CrawlSite

**Purpose**<br />
Crawl site for links using downwards traversal only.
This uses the url_to_absolute() function from http://nadeausoftware.com/articles/2008/05/php_tip_how_convert_relative_url_absolute_url.

**Example**
```php
<?php
$crawler = new CrawlSite();
$links = $crawler('http://example.com/test');
echo '<pre>' . print_r($links, true) . '</pre>';
?>
```
_BECOMES_
```
Array
(
    [http://example.com/test] => Array
        (
            [0] => http://example.com/global.css
            [1] => http://example.com/index.html
            [2] => http://example.com/demo
            [3] => http://example.com/test/subpage.html
            [4] => http://anothersite.com
            [5] => http://example.com/images/sample.jpg
            [6] => http://example.com/scripts.js
        )

    [http://example.com/test/subpage.html] => Array
        (
            [0] => http://example.com/global.css
            [1] => http://example.com/test/video.mp4
            [2] => http://example.com
            [3] => http://example.com/demo
            [4] => http://example.com/test
        )
)
```
