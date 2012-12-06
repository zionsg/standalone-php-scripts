Run PHP-CS-Fixer in Browser
===========================

**Purpose**<br />
Run PHP-CS-Fixer in browser. This would be useful if there is no access to the commandline, eg. restricted permissions.

**Example**<br />

1. Clone this project into your `<webroot>/check`
2. Type the following in your browser: `http://localhost/check/phpcsfixer_browser.php`
3. The script will check `Person.php`
4. You should see the following output (or similar) in your browser:

```
! Class Person in D:/localhost/www/check/Person.php should have at least a vendor namespace according to PSR-0 rules
   1) Person.php (braces, eof_ending)
```
