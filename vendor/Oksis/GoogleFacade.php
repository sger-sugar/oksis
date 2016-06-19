<?php

class Oksis_GoogleFacade {

    /**
     * @var Google_Service_Drive
     */
    protected $service;

    public function __construct()
    {
        $client = getClient();
        $this->service = new Google_Service_Drive($client);
    }

    public function uploadFile($filePath) {
        $this->createItem($filePath, false);
    }

    public function uploadDir($filePath) {
        $this->createItem($filePath, true);
    }

    protected function createItem($filePath, $isDir) {
        $file = new Google_Service_Drive_DriveFile();
        $file->setName(basename($filePath));
        if ($isDir) {
            $file->setMimeType('application/vnd.google-apps.folder');
            $createdFile = $this->service->files->create($file, array());
        } else {
            $file->setMimeType('application/octet-stream');
            $data = file_get_contents($filePath);
            $createdFile = $this->service->files->create($file, array(
                'data' => $data,
                'mimeType' => 'application/zip',
                'uploadType' => 'media'
            ));
        }

        return $createdFile->id;
    }
}