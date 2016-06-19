<?php

class Oksis_Master {

    protected $directories = array();
    protected $files = array();

    public function __construct($path)
    {
        if (!is_dir($path)) {
            throw new Exception('Directory ' . $path . ' not exists.');
        }

        $this->indexDirectory($path);
    }

    protected function indexDirectory($path) {

        $path = realpath($path) . DIRECTORY_SEPARATOR;

        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $name => $object){ /**@var SplFileInfo $object */
            $basename = $object->getBasename();
            if ($basename != '.' && $basename != '..') {
                $relativePath = str_replace($path, '', $name);
                if ($object->isDir()) {
                    $this->directories[$relativePath] = false;
                } else {
                    $this->files[$relativePath] = false;
                }
            }
        }
    }
}