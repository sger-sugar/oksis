<?php

class Oksis_Master {

    protected $directories = array();
    protected $files = array();

    /**
     * @var Oksis_GoogleFacade
     */
    protected $google;

    public function __construct($path)
    {
        if (!is_dir($path)) {
            throw new Exception('Directory ' . $path . ' not exists.');
        }

        $this->indexDirectory($path);

        $this->google = new Oksis_GoogleFacade();
    }

    protected function indexDirectory($path) {

        $dirName = basename($path);
        $this->directories[$dirName] = false;
        $path = realpath($path) . DIRECTORY_SEPARATOR;

        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $name => $object){ /**@var SplFileInfo $object */
            $basename = $object->getBasename();
            if ($basename != '.' && $basename != '..') {
                $relativePath = $dirName . DIRECTORY_SEPARATOR . str_replace($path, '', $name);
                if ($object->isDir()) {
                    $this->directories[$relativePath] = false;
                } else {
                    $this->files[$relativePath] = false;
                }
            }
        }
    }

    public function createDirectories() {

        $rootDirectory = key($this->directories);
        reset($this->directories);
        $rootDirectoryName = $rootDirectory . '-'.date('Y-m-d H i s');
        $id = $this->google->uploadDir($rootDirectoryName);
        $this->directories[$rootDirectory] = $id;

        foreach($this->directories as $path => $elementId) {
            if ($elementId) {
                continue;
            }
            $dirName = basename($path);
            $parent = dirname($path);
            $parentId = $this->directories[$parent];
            $id = $this->google->uploadDir($dirName, $parentId);
            $this->directories[$path] = $id;
        }
    }

    public function uploadFiles() {

        foreach($this->files as $path => $elementId) {
            $parent = dirname($path);
            $parentId = $this->directories[$parent];
            $id = $this->google->uploadFile($path, $parentId);
            $this->files[$path] = $id;
        }
    }
}