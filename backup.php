<?php

var_dump(new backup_dirs("C:\wamp64\www\admin", "C:\wamp64\www\\testt2.zip"));

class backup_dirs {
    
    function __construct($dirs, $destination = "backup_site.zip", $comp = 'gz', $index = 9, $memory = false) {
        if (version_compare(PHP_VERSION, '5.3.0', '<'))
            return $this->err = "PHP Version not supported (>5.3.0).";
        if (is_string($dirs))
            $dirs = explode(',', $dirs);
        elseif (is_array($dirs))
            $dirs = $dirs;
        else
            return $this->err = "Path Error";
        if (is_string($destination) && !is_dir(dirname($destination) . DIRECTORY_SEPARATOR))
            mkdir(dirname($destination) . DIRECTORY_SEPARATOR, 0764, true);
        switch (pathinfo($destination, PATHINFO_EXTENSION)) {
            case 'zip':
                if (class_exists('PharData'))
                    return $this->res = $this->zip($dirs, $destination, $comp);
                elseif (extension_loaded('zip'))
                    return $this->res = $this->zip_2($dirs, $destination, $memory);
                else
                    return $this->res = "Zip not supported.";
                break;
            case 'tar':
                if (class_exists('PharData')) {
                    $res = $this->tar($dirs, $destination, $comp, $index);
                    if ($comp == 'gz' || $comp == 'bz2')
                        unlink(realpath($destination));
                } else {
                    $res = "Tar not supported.";
                }
                return $this->res = $res;
                break;
            default:
                $destination = $destination . '.zip';
                if (class_exists('PharData'))
                    return $this->res = $this->zip($dirs, $destination, $comp);
                elseif (extension_loaded('zip'))
                    return $this->res = $this->zip_2($dirs, $destination, $memory);
                else
                    return $this->res = "Zip Extension not found.";
                break;
        }
    }
    
    function zip($dirs, $destination, $compr) {
        try {
            $fname = realpath(dirname($destination)) . DIRECTORY_SEPARATOR . basename($destination);
            $zip = new PharData($fname, FilesystemIterator::SKIP_DOTS, basename($destination), Phar::ZIP);
            $count = count($dirs);
            foreach ($dirs as $source) {
                if (!file_exists($source)) {
                    if (!isset($err))
                        $err = array(
                            $source
                        );
                    else
                        $err[] = $source;
                    continue;
                }
                $source = realpath($source);
                if (is_dir($source) === true) {
                    $zip->buildFromDirectory($source);
                    if ($compr == 'gz') {
                        $zip->compressFiles(Phar::GZ);
                    } elseif ($compr == 'bz2') {
                        $zip->compressFiles(Phar::BZ2);
                    }
                }
            }
        }
        catch (UnexpectedValueException $e) {
            return $this->err = 'Could not open ' . basename($destination);
        }
        catch (BadMethodCallException $e) {
            return $this->err = 'Technically, this cannot happen';
        }
        if (!isset($err))
            return true;
        else
            return $this->err = "Not exists: " . implode('; ', $err);
    }
    
    function zip_2($dirs, $destination, $memory) {
        $zip = new ZipArchive();
        $res = $zip->open($destination, ZIPARCHIVE::CREATE);
        if ($res !== true)
            return $this->err = "Zip Error (Code $res)";
        $count = count($dirs);
        foreach ($dirs as $source) {
            if (!file_exists($source)) {
                if (!isset($err))
                    $err = array(
                        $source
                    );
                else
                    $err[] = $source;
                continue;
            }
            $source = realpath($source);
            if (is_dir($source) === true) {
                $path = str_replace(dirname($source) . DIRECTORY_SEPARATOR, '', $source);
                $dirs = new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
                $files = new RecursiveIteratorIterator($dirs, RecursiveIteratorIterator::SELF_FIRST);
                while ($files->valid()) {
                    if ($files->isDir()) {
                        if ($count > 1)
                            $zip->addEmptyDir($path . '/' . $files->getSubPath());
                        else
                            $zip->addEmptyDir($files->getSubPath());
                    } elseif ($files->isFile()) {
                        if (realpath($files->getPathname()) != realpath($destination)) {
                            if ($count > 1) {
                                if ($memory)
                                    $zip->addFile(realpath($files->getPathname()), $path . '/' . $files->getSubPathname());
                                else
                                    $zip->addFromString($path . '/' . $files->getSubPathname(), file_get_contents(realpath($files->getPathname())));
                            } else {
                                if ($memory)
                                    $zip->addFile(realpath($files->getPathname()), $files->getSubPathname());
                                else
                                    $zip->addFromString($files->getSubPathname(), file_get_contents(realpath($files->getPathname())));
                            }
                        }
                    }
                    $files->next();
                }
            } else if (is_file($source) === true) {
                $zip->addFromString(basename($source), file_get_contents($source));
            }
        }
        $res = $zip->close();
        if (!isset($err))
            return $this->res = $res;
        else
            return $this->err = "Not exists: " . implode('; ', $err);
    }
    
    function tar($dirs, $destination, $compr, $index) {
        try {
            if (is_int($index) === false || $index > 9 || $index < 1)
                $index = 9;
            $fname = realpath(dirname($destination)) . DIRECTORY_SEPARATOR . basename($destination);
            $tar = new PharData($fname, FilesystemIterator::SKIP_DOTS, basename($destination), Phar::TAR);
            $count = count($dirs);
            foreach ($dirs as $source) {
                if (!file_exists($source)) {
                    if (!isset($err))
                        $err = array(
                            $source
                        );
                    else
                        $err[] = $source;
                    continue;
                }
                $source = realpath($source);
                if (is_dir($source) === true) {
                    $tar->buildFromDirectory($source);
                    if ($compr == 'gz') {
                        file_put_contents($fname . '.gz', gzencode(file_get_contents($fname), $index, FORCE_GZIP));
                    } elseif ($compr == 'bz2') {
                        file_put_contents($fname . '.bz2', bzcompress(file_get_contents($fname), $index));
                    } elseif ($compr == 'deflate') {
                        file_put_contents($fname . '.gz', gzencode(file_get_contents($fname), $index, FORCE_DEFLATE));
                    }
                }
            }
        }
        catch (UnexpectedValueException $e) {
            return $this->err = 'Could not open ' . basename($destination);
        }
        catch (BadMethodCallException $e) {
            return $this->err = 'Technically, this cannot happen';
        }
        if (!isset($err))
            return true;
        else
            return $this->err = "Not exists: " . implode('; ', $err);
    }
    
}

?>