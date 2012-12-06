### Run Composer install in Browser

**Purpose**<br />
Run `composer install` in browser.<br />
This can be used if there is no access to the commandline, eg. restricted permissions.

**Example**<br />

1. Clone this project into your `<webroot>/getcomposer`
2. Download `composer.phar` from https://getcomposer.org/composer.phar into your `<webroot>/getcomposer`
3. Type the following in your browser: `http://localhost/getcomposer/composer_browser.php`
4. The script will read `composer.json` and install PHPUnit into your `<webroot>/getcomposer/PHPUnit`
5. After a while, you should see the following output (or similar) in your browser:

```
#!/usr/bin/env php
```
