<?php

class Oksis_Application {
    const DISPLAY_MODE_NORMAL = 'n';
    const DISPLAY_MODE_QUIET = 'q';
    const DISPLAY_MODE_FULL = 'f';

    const SHARED_MEMORY_PATHNAME = __FILE__;
    const SHARED_MEMORY_PROJECT = 'o';
    protected $sharedMemoryResource = null;

    protected $mode = self::DISPLAY_MODE_NORMAL;

    protected $config = array();

    /**
     * @var Oksis_Application
     */
    protected static $instance = null;

    /**
     * @return Oksis_Application
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {
        $this->checkHasAccess();
        $this->setDisplayMode();
        $this->loadConfig();
        $this->createSharedMemory();
    }

    protected function checkHasAccess() {
        getClient();
    }

    protected function createSharedMemory() {
        if ($this->mode == self::DISPLAY_MODE_FULL) {
            $key = ftok(self::SHARED_MEMORY_PATHNAME, self::SHARED_MEMORY_PROJECT);
            $this->sharedMemoryResource = shm_attach($key, 5*1024*1024);
        }
    }

    protected function loadConfig() {
        $config = null;
        include 'config.php';
        if ($config == null) { die('Missing $config variable in config.php'); }
        if (!isset($config['threadCount'])) { die('Missing "threadCount" parameter in config.php'); }
        if (!isset($config['uploadPath'])) { die('Missing "uploadPath" parameter in config.php'); }

        $this->config = $config;
    }

    public function getConfigValue($key) {
        return $this->config[$key];
    }

    protected function setDisplayMode() {
        global $argv;
        if (in_array('-q', $argv)) {
            $this->mode = self::DISPLAY_MODE_QUIET;
        } elseif (in_array('-f', $argv)) {
            $this->mode = self::DISPLAY_MODE_FULL;
        } elseif (in_array('-n', $argv)) {
            $this->mode = self::DISPLAY_MODE_NORMAL;
        }
    }

    public function go() {
        global $argv;
        if (in_array('-d', $argv)) {
            $this->doDirectory();
        } else {
            $this->doMain();
        }
    }

    protected function doMain() {
        $output = array();
        $file = basename($_SERVER['PHP_SELF']);
        $result = exec('php ./' . $file . ' -d', $output); // create directories in another process because of libcurl inner bug
        $directories = json_decode($result, true);
        if (!is_array($directories)) {
            exit($result);
        }

        if ($this->mode == self::DISPLAY_MODE_FULL) {
            foreach ($directories as $directory => $id) {
                echo $directory . ' uploaded with id=' . $id . PHP_EOL;
            }
        }

        if ($this->mode != self::DISPLAY_MODE_QUIET) {
            echo 'ALL DIRECTORIES WERE CREATED at ' . date('Y-m-d H:i:s') . PHP_EOL;
        }

        $threadCount = $this->getConfigValue('threadCount');

        $master = new Oksis_FileManager($this->getConfigValue('uploadPath'), $threadCount);
        $master->setDirectories($directories);
        $master->prepareFiles();

        $forkId = $master->forkThreads();
        if ($forkId == Oksis_FileManager::MASTER_FORK_ID) {
            $status = null;
            pcntl_wait($status);
            if ($this->mode == self::DISPLAY_MODE_FULL) {
                for ($i = 1; $i <= $threadCount; $i++) {
                    if (shm_has_var($this->sharedMemoryResource, $i)) {
                        $log = shm_get_var($this->sharedMemoryResource, $i);
                        echo $log . PHP_EOL;
                    }
                }
            }
            if ($this->mode != self::DISPLAY_MODE_QUIET) {
                echo 'ALL FILES WERE UPLOADED at ' . date('Y-m-d H:i:s') . PHP_EOL;
            }
            $this->destroySharedMemory();
        } else {
            $log = $master->uploadFiles();
            if ($this->mode == self::DISPLAY_MODE_FULL) {
                shm_put_var($this->sharedMemoryResource, $forkId, $log);
            }
        }
    }

    protected function doDirectory() {

        $master = new Oksis_FileManager(
            $this->getConfigValue('uploadPath'),
            $this->getConfigValue('threadCount')
        );
        $directories = $master->createDirectories();
        exit(json_encode($directories));
    }

    protected function destroySharedMemory()
    {
        if (is_resource($this->sharedMemoryResource)) {
            shm_remove($this->sharedMemoryResource);
        }
    }
}