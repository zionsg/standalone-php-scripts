### Run PHPUnit in Browser

**Purpose**<br />
Run PHPUnit in browser using `phpunit.xml` or `phpunit.xml.dist` for configuration.<br />
This can be used if there is no access to the commandline, eg. restricted permissions.

**Example**<br />

1. Clone this project into your `<webroot>/test`
2. Run `composer install` to install `PHPUnit`
3. Type the following in your browser: `http://localhost/test/phpunit_browser.php`
4. The script will read `phpunit.xml` and run `testsuite/PersonTest.php`
5. You should see the following output (or similar) in your browser:

```
PHPUnit 3.7.10 by Sebastian Bergmann.

Configuration read from D:\localhost\www\test\phpunit.xml

.

Time: 0 seconds, Memory: 3.00Mb

OK (1 test, 1 assertion)
```
