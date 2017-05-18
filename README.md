# Backup folders

## Compressing a folder and its subfolders in different formats (.zip | .tar.gz | .tar.bz2).

```php
function __construct($dirs, $destination = "backup_site.zip", $comp = 'gz', $index = 9, $memory = false) {}
```


1. $dirs (String or Array) => The folder/s you need to backup. (Even more folders)
1. $destination => Destination folder.
1. $comp => Compression type. (Usually no need to modify it)
	- Zlib => gz
    - Bzip2 => bz2 
    - Zlib => deflate
1. $index => Compression index. (Usually no need to modify it) Min 0 | Max 9
1. $memory => ONLY in case of memory problems, set it to true (It's not miraculous).

## USE

```php
$from_dir = "../admin";
$to_filepath = "../backup_files.zip"; // or "../backup_files.tar"
$bk = new backup_dirs($from_dir, $to_filepath);
if($bk->res !== true) echo $bk->err;
else echo "Done";
```

```php
$from_dir = array("../admin","../cache","../config");
$to_filepath = "../backup_files.zip"; // or "../backup_files.tar"
$bk = new backup_dirs($from_dir, $to_filepath);
if($bk->res !== true) echo $bk->err;
else echo "Done";
```
Required PHP Version > 5.3.0 | PHAR Extension or Zip Extension.