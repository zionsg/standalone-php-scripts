Directory Listing
=================

**Purpose**<br />
Retrieve directory listing with folder/file count and size info

**Example**
```php
<style>
  .directoryListing { font-family: "Courier New"; }
  .folder { font-weight: bold; }
  .file { color: blue; }
  .level0 { color: red; }
  .file.level0 { color: green; }
</style>

<?php
$instance = new DirectoryListing('D:\testParent');

// Using filter callback
$filterCallback = function($path) {
    if (is_file($path)) {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return (!preg_match('~^(exe|zip)$~', $extension));
    } elseif (is_dir($path)) {
        return (basename($path) != 'HIDDEN');
    }
};
echo '[WITH FILTER]<br />' . $instance($filterCallback) . '<br /><br />';

// No filters
echo '[NO FILTER]<br />' . $instance();
?>
```
_BECOMES_

<!-- Colors not supported in Markdown yet, so using screenshot instead -->
![Screenshot of result](DirectoryListing/screenshot.png)

<!--
<style>
  .directoryListing { font-family: "Courier New"; }
  .folder { font-weight: bold; }
  .file { color: blue; }
  .level0 { color: red; }
  .file.level0 { color: green; }
</style>
-->
<!--
[WITH FILTER]

<div class="directoryListing" style="font-family:Courier New;">

Directory Listing for D:\testParent<br /><br />
Total folders and files filtered out: 2<br /><br />
* Type * Path * Total Size (Bytes) * Total Size (Human-Readable) * Folders * Files * Total Nested Folders * Total Nested Files *<br /><br />
<span class="folder level0">* FOLDER * D:\testParent * 712778 bytes * 696.07 KiB * Folders: 1 * Files: 1 * Total nested folders: 2 * Total nested files: 3 *</span><br />
&nbsp;&nbsp;<span class="file level0">* File * New Text Document.txt * 20 bytes * 20.00 B *</span><br />
&nbsp;&nbsp;<span class="folder level1">* FOLDER * child * 712758 bytes * 696.05 KiB * Folders: 1 * Files: 2 * Total nested folders: 1 * Total nested files: 2 *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;<span class="file level1">* File * Getting-Started-with-Zend-Framework-2.pdf * 712754 bytes * 696.05 KiB *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;<span class="file level1">* File * sample.txt * 4 bytes * 4.00 B *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;<span class="folder level2">* FOLDER * grandchild * 0 bytes * 0.00 B * Folders: 0 * Files: 0 * Total nested folders: 0 * Total nested files: 0 *</span><br />

</div>


[NO FILTER]

<div class="directoryListing">

Directory Listing for D:\testParent<br /><br />
Total folders and files filtered out: 0<br /><br />
* Type * Path * Total Size (Bytes) * Total Size (Human-Readable) * Folders * Files * Total Nested Folders * Total Nested Files *<br /><br />
<span class="folder level0">* FOLDER * D:\testParent * 63939129 bytes * 60.98 MiB * Folders: 1 * Files: 1 * Total nested folders: 3 * Total nested files: 5 *</span><br />
&nbsp;&nbsp;<span class="file level0">* File * New Text Document.txt * 20 bytes * 20.00 B *</span><br />
&nbsp;&nbsp;<span class="folder level1">* FOLDER * child * 63939109 bytes * 60.98 MiB * Folders: 2 * Files: 2 * Total nested folders: 2 * Total nested files: 4 *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;<span class="file level1">* File * Getting-Started-with-Zend-Framework-2.pdf * 712754 bytes * 696.05 KiB *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;<span class="file level1">* File * sample.txt * 4 bytes * 4.00 B *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;<span class="folder level2">* FOLDER * HIDDEN * 63210976 bytes * 60.28 MiB * Folders: 0 * Files: 1 * Total nested folders: 0 * Total nested files: 1 *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="file level2">* File * skip.exe * 63210976 bytes * 60.28 MiB *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;<span class="folder level2">* FOLDER * grandchild * 15375 bytes * 15.01 KiB * Folders: 0 * Files: 1 * Total nested folders: 0 * Total nested files: 1 *</span><br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="file level2">* File * ignore.zip * 15375 bytes * 15.01 KiB *</span><br />

</div>
-->