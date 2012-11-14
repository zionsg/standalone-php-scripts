Virtual Hosts
=============

**Purpose**<br />
Generate list and config for port-based virtual hosts on local development machine.

**Example**
Sample directory structure of server web root
  D:\EasyPHP\www
    alpha.com
      www
    beta.com
      www
    gamma.net
      www
```php
<?php
$instance = new VirtualHosts();
$params = array(
    'filterFunction' => function($folder) {
        return ('gamma.net' !== $folder);
    },
    'showConfig' => true,
);
echo $instance($params);
?>
```
_BECOMES_
<ul>
  <li><a href="http://127.0.0.1:4388">alpha.com</a> (127.0.0.1:4388)</li>
  <li><a href="http://127.0.0.1:3509">beta.com</a> (127.0.0.1:3509)</li>
</ul>
<br /><br />
[CONFIG FOR APACHE HTTPD.CONF]<br /><br />
# alpha.com<br />
Listen 127.0.0.1:4388<br />
NameVirtualHost 127.0.0.1:4388<br />
&lt;VirtualHost 127.0.0.1:4388&gt;<br />
  DocumentRoot &quot;${path}\alpha.com\www&quot;<br />
&lt;/VirtualHost&gt;<br />
<br />
# beta.com<br />
Listen 127.0.0.1:3509<br />
NameVirtualHost 127.0.0.1:3509<br />
&lt;VirtualHost 127.0.0.1:3509&gt;<br />
  DocumentRoot &quot;${path}\beta.com\www&quot;<br />
&lt;/VirtualHost&gt;<br />
<br />