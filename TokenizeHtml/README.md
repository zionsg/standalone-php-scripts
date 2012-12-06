### Tokenize Html

**Purpose**<br />
Tokenize HTML content into words and tags

**Example**
```php
<?php
$instance = new TokenizeHtml();
$html = 'The quick <div>brown <a href="http://fox.com">fox '
      . '<a href="jumps.jpg">jumps</a></a> over</div> the lazy old dog. ';
var_dump($instance($html));
?>
```
_BECOMES_
<pre class='xdebug-var-dump' dir='ltr'>
<b>array</b>
  0 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'The'</font> <i>(length=3)</i>
  1 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'quick'</font> <i>(length=5)</i>
  2 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'&lt;div&gt;'</font> <i>(length=5)</i>
  3 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'brown'</font> <i>(length=5)</i>
  4 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'&lt;a href=&quot;http://fox.com&quot;&gt;'</font> <i>(length=25)</i>
  5 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'fox'</font> <i>(length=3)</i>
  6 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'&lt;a href=&quot;jumps.jpg&quot;&gt;'</font> <i>(length=20)</i>
  7 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'jumps'</font> <i>(length=5)</i>
  8 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'&lt;/a&gt;'</font> <i>(length=4)</i>
  9 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'&lt;/a&gt;'</font> <i>(length=4)</i>
  10 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'over'</font> <i>(length=4)</i>
  11 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'&lt;/div&gt;'</font> <i>(length=6)</i>
  12 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'the'</font> <i>(length=3)</i>
  13 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'lazy'</font> <i>(length=4)</i>
  14 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'old'</font> <i>(length=3)</i>
  15 <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'dog.'</font> <i>(length=4)</i>
</pre>