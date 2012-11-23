Tokenize
========

**Purpose**<br />
Tokenize string into words and HTML tags

**Example**
```php
<?php
$instance = new Tokenize();
$html = 'The quick <div>brown <a href="http://fox.com">fox '
      . '<a href="jumps.jpg">jumps</a></a> over</div> the lazy old dog. ';
var_dump($instance($html));
?>
```
_BECOMES_
```
<b>array</b>
  0 =&gt; <small>string</small> 'The' <i>(length=3)</i>
  1 =&gt; <small>string</small> 'quick' <i>(length=5)</i>
  2 =&gt; <small>string</small> '&lt;div&gt;' <i>(length=5)</i>
  3 =&gt; <small>string</small> 'brown' <i>(length=5)</i>
  4 =&gt; <small>string</small> '&lt;a href=&quot;http://fox.com&quot;&gt;' <i>(length=25)</i>
  5 =&gt; <small>string</small> 'fox' <i>(length=3)</i>
  6 =&gt; <small>string</small> '&lt;a href=&quot;jumps.jpg&quot;&gt;' <i>(length=20)</i>
  7 =&gt; <small>string</small> 'jumps' <i>(length=5)</i>
  8 =&gt; <small>string</small> '&lt;/a&gt;' <i>(length=4)</i>
  9 =&gt; <small>string</small> '&lt;/a&gt;' <i>(length=4)</i>
  10 =&gt; <small>string</small> 'over' <i>(length=4)</i>
  11 =&gt; <small>string</small> '&lt;/div&gt;' <i>(length=6)</i>
  12 =&gt; <small>string</small> 'the' <i>(length=3)</i>
  13 =&gt; <small>string</small> 'lazy' <i>(length=4)</i>
  14 =&gt; <small>string</small> 'old' <i>(length=3)</i>
  15 =&gt; <small>string</small> 'dog.' <i>(length=4)</i>
```