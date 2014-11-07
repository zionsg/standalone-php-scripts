### Virtual Hosts

**Purpose**<br />
Generate list and config for port-based virtual hosts on local development machine.

**Example**<br />
```
Sample directory structure of server web root
  D:\EasyPHP\www
    alpha.com
      public_html
    beta.com
      public_html
    gamma.net
      public_html
```
```php
<?php
$instance = new VirtualHosts();
$params = array(
    'filterFunction' => function($folder) {
        return ('gamma.net' !== $folder);
    },
);
print_r($instance($params));
?>
```
_BECOMES_
```
Array
(
    [alpha.com] => Array
        (
            [uri] => 127.0.0.1:4388
            [duplicatePort] => false
            [config] => 
                # alpha.com
                Listen 127.0.0.1:4388
                NameVirtualHost 127.0.0.1:4388
                <VirtualHost 127.0.0.1:4388>
                  DocumentRoot "${path}/alpha.com/public_html"
                </VirtualHost>
        )

    [beta.com] => Array
        (
            [uri] => 127.0.0.1:3509
            [duplicatePort] => false
            [config] => 
                # beta.com
                Listen 127.0.0.1:3509
                NameVirtualHost 127.0.0.1:3509
                <VirtualHost 127.0.0.1:3509>
                  DocumentRoot "${path}/beta.com/public_html"
                </VirtualHost>
        )
)
```
