<?php

class Oksis_Master {

    protected $directories = array();
    protected $files = array();

    protected $treadCount;
    protected $packs = array();

    const PACK_SIZE_FILES = 10;

    /**
     * @var Oksis_GoogleFacade
     */
    protected $google;

    public function __construct($path, $treadCount)
    {
        $this->treadCount = $treadCount;

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
            echo "$path created" . PHP_EOL;
        }

        $this->fillFileDirectories();
        $this->makePacks();
    }

    public function uploadFiles() {

        foreach($this->files as $path => $elementId) {
            $parent = dirname($path);
            $parentId = $this->directories[$parent];
            $id = $this->google->uploadFile($path, $parentId);
            $this->files[$path] = $id;
            echo "$path uploaded" . PHP_EOL;
        }
    }

    protected function fillFileDirectories() {
        foreach($this->files as $file => $false) {
            $fileDir = dirname($file);
            $this->files[$file] = $this->directories[$fileDir];
        }
    }

    protected function makePacks() {
        $this->packs = array_chunk($this->files, Oksis_Master::PACK_SIZE_FILES, true);
    }
}