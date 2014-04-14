# Python script for bulk conversion of HTML and PHP files to UTF-8 without BOM using Notepad++
#
# @author Zion Ng <zion@intzone.com>
# @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/CrawlSite
# @see    http://notepad-plus-plus.org/ for Notepad++
# @see    http://npppythonscript.sourceforge.net/ for PythonScript plugin for Notepad++
#

import os;
import sys;

# Modify path and extensions of files to convert here
path = 'D:\\localhost\\files'
extensions = ['.htm', '.html', '.php', '.phtml']

# Function to convert files in dir to UTF-8 without BOM using Notepad++
def processDirectory(args, dir, files):
    console.write('Directory: ' + dir + "\r\n")
    for file in files:
      for extension in extensions:
        if file[-len(extension):] == extension:
          console.write('    ' + file + "\r\n")
          notepad.open(dir + "\\" + file)
          notepad.runMenuCommand('Encoding', 'Convert to UTF-8 without BOM')
          notepad.save()
          notepad.close()
          break

# Run script
# processDirectory() will be called recursively for each directory encountered
os.path.walk(path, processDirectory, None)
console.write("\r\n" + 'DONE!' + "\r\n")
