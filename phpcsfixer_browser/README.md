### Run PHP-CS-Fixer in Browser

**Purpose**<br />
Run PHP-CS-Fixer in browser using .php_cs for configuration.<br />
This can be used if there is no access to the commandline, eg. restricted permissions.

**Example**<br />

1. Clone this project into your `<webroot>/check`
2. Run `composer install` to install `PHP-CS-Fixer`
3. Type the following in your browser: `http://localhost/check/phpcsfixer_browser.php`
4. The script will check `NonCompliantPerson.php`
5. You should see the following output (or similar) in your browser:

```
! Class NonCompliantPerson in D:/localhost/www/check/NonCompliantPerson.php should have at least a vendor namespace according to PSR-0 rules
   1) NonCompliantPerson.php (braces, eof_ending)
```
