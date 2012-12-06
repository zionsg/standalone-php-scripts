Run PHPUnit in Browser
======================

**Purpose**<br />
Run PHPUnit in browser. This would be useful if there is no access to the commandline, eg. restricted permissions.

**Example**<br />

1. Clone this project into your `<webroot>/test`.
2. Type the following in your browser: `http://localhost/test/phpunit_browser.php`
3. The script will load `PersonTest.php` (which uses `Person.php`)
4. You should see the following output (or similar) in your browser:

```
PHPUnit 3.7.10 by Sebastian Bergmann.

Configuration read from D:\localhost\www\test\phpunit.xml

.

Time: 0 seconds, Memory: 3.00Mb

OK (1 test, 1 assertion)
```
