Standalone PHP Scripts
======================

In general, folders with names in `StudlyCaps` contain classes (OOP) while those in lowercase/camelCase contain
functions or procedural scripts.

**Usage**

To clone just a folder, follow these steps taken from a [StackOverflow post](http://stackoverflow.com/questions/600079/is-there-any-way-to-clone-a-git-repositorys-sub-directory-only/28039894#28039894):
```
mkdir <repo>
cd <repo>
git init
git remote add origin <url>
git config core.sparsecheckout true
echo "<folderInRepo>/*" >> .git/info/sparse-checkout
git pull --depth=1 origin master
```

**Classes**
- CompareDatabases<br>
  Compare columns and rows between 2 databases.

- CrawlSite<br>
  Crawl website using downwards traversal only.

- DirectoryListing<br>
  Retrieve directory listing with folder/file count and size info.

- Grep<br>
  Grep (UNIX command) all files for search text recursively in current directory.

- InlineImages<br>
  Get url content and replace images with inline images.

- ReplaceShortTags<br>
  Reads PHP file and replaces short tags

- SplitSchoolClass<br>
  Split text containing school class names into individual classes.

- TextToCalendar<br>
  Generate Excel calendar from formatted text using PHPExcel library (http://phpexcel.codeplex.com).

- TokenizeHtml<br>
  Tokenize HTML content into words and tags

- VirtualHosts<br>
  Generate list and config for port-based virtual hosts on local development machine.

**Procedural scripts**
- alignText<br>
  Align text in columns.

- array_diff_key_recursive<br>
  Compare keys of 2 arrays recursively in both directions.

- array_reduce_lists_recursive<br>
  Reduce lists in an array to 1 element each recursively.

- composer_browser<br>
  Run `composer install` in browser.

- find_long_filenames<br>
  Find long filenames in path.

- getExcelSubstituteFormula<br>
  Generate Excel formula for substituting multiple words.

- getMacIpFromArp<br>
  Get MAC addresses and their IPv4 addresses from `arp -a` output

- permutate<br>
  Generate permutations of a fixed length given a list of characters.

- phpcsfixer_browser<br>
  Run PHP-CS-Fixer in browser (checking only, no fixing).

- phpunit_browser<br>
  Run PHPUnit tests in browser.

- pixel<br>
  Output 1-pixel transparent GIF image.

- rename_files<br>
  Rename files in folder especially images with numbering

- replacePlaceholders<br>
  Replace placeholders in format string with variable values

- show_database_schema<br>
  List out all the tables in a database together with the column information

- split_sql_file<br>
  Split large SQL database dump file into smaller parts
