<?php

class Oksis_Application {
    const DISPLAY_MODE_NORMAL = 'n';
    const DISPLAY_MODE_QUIET = 'q';
    const DISPLAY_MODE_FULL = 'f';

    protected $mode = self::DISPLAY_MODE_NORMAL;

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
        global $argv;
        if (in_array('-q', $argv)) {
            $this->mode = self::DISPLAY_MODE_QUIET;
        } elseif (in_array('-f', $argv)) {
            $this->mode = self::DISPLAY_MODE_FULL;
        } elseif (in_array('-n', $argv)) {
            $this->mode = self::DISPLAY_MODE_NORMAL;
        }
    }

    public function doMain() {

        $dir = __DIR__ . '/vendor';
        $treadCount = 3;

        $output = array();
        $result = exec('php ./directory.php', $output); // create directories in another process because of libcurl inner bug
        $directories = json_decode($result, true);
        if (!is_array($directories)) {
            exit($result);
        }

        echo 'ALL DIRECTORIES CREATED at ' . date('Y-m-d H:i:s') . PHP_EOL;

        $master = new Oksis_Master($dir, $treadCount);
        $master->setDirectories($directories);
        $master->prepareFiles();

        $forkId = $master->forkThreads();
        if ($forkId == Oksis_Master::MASTER_FORK_ID) {
            $status = null;
            pcntl_wait($status);
            echo 'ALL FILES UPLOADED at ' . date('Y-m-d H:i:s') . PHP_EOL;
        } else {
            $log = $master->uploadFiles();
            file_put_contents($forkId . '.txt', $log);
        }
    }

    public function doDirectory() {

        $dir = __DIR__ . '/vendor';
//$dir = '/var/www/firma-rukodelie.ru';

        $treadCount = 3;

        $master = new Oksis_Master($dir, $treadCount);
        $directories = $master->createDirectories();
        exit(json_encode($directories));
    }
}