<?php

class Oksis_Application {
    const DISPLAY_MODE_NORMAL = 'n';
    const DISPLAY_MODE_QUIET = 'q';
    const DISPLAY_MODE_FULL = 'f';

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
        $this->setDisplayMode();
        $this->loadConfig();
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

        if ($this->mode != self::DISPLAY_MODE_FULL) {
            foreach ($directories as $directory => $id) {
                echo $directories . ' uploaded with id=' . $id . PHP_EOL;
            }
        }

        if ($this->mode != self::DISPLAY_MODE_QUIET) {
            echo 'ALL DIRECTORIES CREATED at ' . date('Y-m-d H:i:s') . PHP_EOL;
        }

        $master = new Oksis_FileManager($this->getConfigValue('uploadPath'), $this->getConfigValue('threadCount'));
        $master->setDirectories($directories);
        $master->prepareFiles();

        $forkId = $master->forkThreads();
        if ($forkId == Oksis_FileManager::MASTER_FORK_ID) {
            $status = null;
            pcntl_wait($status);
            if ($this->mode != self::DISPLAY_MODE_QUIET) {
                echo 'ALL FILES UPLOADED at ' . date('Y-m-d H:i:s') . PHP_EOL;
            }
        } else {
            $log = $master->uploadFiles();
            file_put_contents($forkId . '.txt', $log);
        }
    }

    protected function doDirectory() {

        $master = new Oksis_FileManager($this->getConfigValue('uploadPath'), $this->getConfigValue('threadCount'));
        $directories = $master->createDirectories();
        exit(json_encode($directories));
    }
}