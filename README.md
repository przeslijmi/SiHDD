# Przeslijmi SiHDD

[![Run Status](https://api.shippable.com/projects/5e4da2352d50ee0007e90b90/badge?branch=master)]()

[![Coverage Badge](https://api.shippable.com/projects/5e4da2352d50ee0007e90b90/coverageBadge?branch=master)]()

Used to perform HDD operations (read and save files and directories).

## Table of contents

  1. [Serving directories](#serving-directories)
    1. [Reading directory](#reading-directory)
    1. [Creating directory](#creating-directory)
    1. [All directory methods](#all-directory-methods)
  1. [Serving files](#serving-files)
  1. [Serving paths](#serving-paths)
  1. [Optional settings](#optional-settings)

## Serving directories

### Reading directory

In `$contents` variable there will be an simple array with list of all files and directories inside given dir.

```php
$dir = new Dir('subdir/');
$contents = $dir->read();
$howMany  = $dir->count();
```

Both `read()` and `count()` method can be enhanced with three parameters:
  - **0**: mask, eg. `*.php` to find only files with php extension,
  - **1**: only files boolean toggle (set to false as default),
  - **1**: only directories boolean toggle (set to false as default).

Examples:

```php
$dir->read('*.php', true); // will list only *.php files
$dir->read(null, false, true); // will list all directories
```

If you need file last modification times use:
```php
$dir = new Dir('subdir/');
$contents = $dir->addFilesMtimes();
```
or if you want still to use a mask:
```php
$dir = new Dir('subdir/');
$dir->read('*.php', true);
$contents = $dir->addFilesMtimes();
```

If you want to read recursively use:
```php
$dir = new Dir('subdir/', Path::DIR_READ_RECURSIVELY);
```

**BEWARE** of using `read()` method respectively with other methods. If you use `read()` without any parameters - it will read all contents of directory. Then it is ok to test if eg. counter of all files and counter of all directories are proper. But if you narrow reading already at `read()` then counting will return false values because counting is no longer analysing all contents but only those that were read. See example below:

Assume that in directory `subdir/` there is one subdirectory and one file.

```php
$dir = new Dir('subdir/');
$dir->read();

echo $dir->countDirs(); // will return 1
```

But if you will narrow reading in the beginning the result will be **zero**:
```php
$dir = new Dir('subdir/');
$dir->read(null, true); // read only files

echo $dir->countDirs(); // will return 0
```

### Creating directory

This is done by `Path` class that underlies both `File` and `Dir` classes. Method shown below will create all directories recursively in one command.

```php
$dir = new Dir('longer/path/to/a/subdir/');
$dir->createDirs();
```

### All directory methods

All existing methods for `Dir` are:
  - `read()`
  - `count()`
  - `countFiles()`
  - `countDirs()`
  - `addFilesMtimes()`

See more at [Serving paths](#serving-paths) chapter.

## Serving files

Reading contents of the file.
```php
$file = new File('directory/file.txt');
$file->read();
$contents = $file->getContents();
```

Saving contents of the file:
```php
$file = new File('directory/file.txt');
$file->setContents('test');
$file->save();
```

All existing methods for `File` are:
  - `setContents()`
  - `getContents()` - only returns contents if read (if not returns null)
  - `read()` - reads and returns contents
  - `readIfExists()`
  - `save()`
  - `append()` - without new line
  - `appendLine()` - with new line
  - `delete()`
  - `deleteIfExists()`
  - `create()` - and make empty (throws if already exists)

See more at [Serving paths](#serving-paths) chapter.

## Serving paths

`Path` object is a parent object of both `File` and `Dir` object and serves extra methods for both of them:
  - `isExisting()`
  - `isNotExisting()`
  - `isDir()`
  - `isNotDir()`
  - `isFile()`
  - `isNotFile()`
  - `getPath()` - returns string path to the `File` or `Dir`
  - `createDirs()`
  - `deleteEmptyDirs()`

## Optional settings

**ALLOW_NATIONAL_LETTERS_NAMES** blocks exception that would throw if file or directory name has national leter in it.

**ALLOW_SPACES_IN_NAMES** as above but for spaces.

**DIR_READ_RECURSIVELY** reading of directories (and using masks as well) is done recursively.

**DIR_READ_IGNORE_DIRS** and **DIR_READ_IGNORE_FILES** ignore these elements during reading of directory.

