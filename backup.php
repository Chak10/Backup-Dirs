<?php
function backup_dirs($dirs, $destination = "backup_site.zip", $memory = false) {
    if (!extension_loaded('zip'))
        return "Zip Extension not found.";
    if (version_compare(PHP_VERSION, '5.3.0', '<'))
        return "PHP Version not supported (>5.3.0).";
    if (is_string($dirs))
        $dirs = explode(',', $dirs);
    elseif (is_array($dirs))
        $dirs = $dirs;
    else
        return "Path Error";
    if (is_string($destination) && !is_dir(dirname($destination) . DIRECTORY_SEPARATOR))
        mkdir(dirname($destination) . DIRECTORY_SEPARATOR, 0764, true);
    $zip = new ZipArchive();
    $res = $zip->open($destination, ZIPARCHIVE::CREATE);
    if ($res !== true)
        return "Zip Error (Code $res)";
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
        return $res;
    else
        return "Not exists: " . implode('; ', $err);
}
?>
