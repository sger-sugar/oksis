<?php

class Oksis_Master {

    protected $directories = array();
    protected $files = array();

    protected $treadCount;
    protected $packs = array();

    const MASTER_FORK_ID = 0;
    protected $forkPids = array();
    protected $forkId;

    const PACK_SIZE_FILES = 3;

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
        $log = '';
        foreach($this->packs[$this->forkId] as $path => $elementId) {
            $parent = dirname($path);
            $parentId = $this->directories[$parent];
            $id = $this->google->uploadFile($path, $parentId);
            $this->files[$path] = $id;
            $log .= "$path uploaded" . PHP_EOL;
        }
        return rtrim($log);
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

    public function forkThreads() {
        if (sizeof($this->packs) < $this->treadCount) {
            $this->treadCount = sizeof($this->packs);
        }

        for ($forkId = 1; $forkId < $this->treadCount+1; $forkId++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('Could not fork. Install pcntl extension first.');
            } else if ($pid) {
                // we are the parent
                pcntl_wait($status); //Protect against Zombie children
                $this->forkPids[$forkId] = $pid;
            } else {
                // we are the child
                $this->forkId = $forkId;
                $this->google = new Oksis_GoogleFacade();
                return $forkId;
            }
        }
        $this->forkId = self::MASTER_FORK_ID;
        return self::MASTER_FORK_ID;
    }
}