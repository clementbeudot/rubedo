<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is licensed exclusively to Inovia Team.
 *
 * @copyright  Copyright (c) 2015 Inovia Team (http://www.inovia.fr)
 * @license      All rights reserved
 * @author       The Inovia Dev Team
 *
 */

namespace Inovia\DumpRestore\Service;

use Rubedo\Services\Manager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class Migration implements ServiceManagerAwareInterface {

    const REVISION_IN_FILENAME_INDEX    = 0;

    protected $migrationFolder;
    protected $serviceManager;
    protected $migrationRepository;

    /**
     * @param $migrationFolder
     * @return $this
     * @throws \Inovia\Exceptions\Migration
     */
    public function setMigrationFolder($migrationFolder)
    {
        if (!is_dir($migrationFolder) || !is_readable($migrationFolder)) {
            throw new \Inovia\Exceptions\Migration('The folder given for migration does not exist or is not readable.', "Exception45");
        }

        $this->migrationFolder = $migrationFolder;

        return $this;
    }

    /**
     * @return $this
     */
    public function getMigrationFolder()
    {
        return $this->migrationFolder;
    }

    /**
     *
     */
    public function __construct()
    {
        $this->migrationFolder = __DIR__ . '/../../../../../../data/migration';;
    }

    public function migrate()
    {
        $migrationRepository = $this->getMigrationRepository();

        $migrationRepository->create(
            array(
                'migration_number' => 1,
                'name' => 'test.zip',
            )
        );
        //$latestMigrationRevision = $migrationRepository->findLatestMigrationRepository();

        $latestMigrationRevision = 0;
        $zipFilesAvailableForMigration = $this->getAvailableZipFilesForMigration($latestMigrationRevision);

        foreach ($zipFilesAvailableForMigration as $revision => $zipFileAvailableForMigration) {
            $unzippedDirectory = $this->unzipMigrationPackage($revision, $zipFileAvailableForMigration);
            $result = $this->executeMigration($unzippedDirectory);

            if ($result === false) {

            } else {

            }
        }
    }

    protected function getAvailableZipFilesForMigration($migrationRevision)
    {
        $availableFiles     = scandir($this->getMigrationFolder(), 1);
        $filesForMigration  = array();
        foreach ($availableFiles as $availableFile) {
            if ($availableFile === '.' || $availableFile === '..') {
                continue;
            }

            if (is_dir($this->getMigrationFolder() . DIRECTORY_SEPARATOR . $availableFile)) {
                continue;
            }

            $filenameExploded = explode('_', $availableFile);
            if (isset($filenameExploded[self::REVISION_IN_FILENAME_INDEX])) {
                $filenameRevision = (int)$filenameExploded[self::REVISION_IN_FILENAME_INDEX];
                if ($filenameRevision > $migrationRevision) {
                    $filesForMigration[$filenameRevision] = $this->getMigrationFolder() . DIRECTORY_SEPARATOR . $availableFile;
                }
            }
        }
        ksort($filesForMigration);
        return $filesForMigration;
    }

    protected function unzipMigrationPackage($revision, $zipFile)
    {
        $zipArchive = new \ZipArchive();
        $zipArchive->open($zipFile);

        $zipArchive->extractTo($this->getTemporaryDirectoryForUnZip() . DIRECTORY_SEPARATOR . $revision);

        return $this->getTemporaryDirectoryForUnZip() . DIRECTORY_SEPARATOR . $revision;
    }

    protected function getTemporaryDirectoryForUnZip()
    {
        return '/tmp';
    }

    protected function executeMigration($temporaryDirectoryWithMigration)
    {
        $extractedFiles = scandir($temporaryDirectoryWithMigration);
        $mimeTypes      = finfo_open(FILEINFO_MIME_TYPE);
        $restoreMode    = 'UPSERT';
        $fileService = Manager::getService('MongoFileAccess');
        $fileService->init();

        $fileCollectionService = Manager::getService('Files');
        $restoredElements = array();

        foreach($extractedFiles as $file) {
            $dataAccessService = Manager::getService('MongoDataAccess');

            if($file !== "." && $file !== "..") {
                $filePath = $temporaryDirectoryWithMigration."/".$file;
                $fileExtension = finfo_file($mimeTypes, $filePath);

                switch (pathinfo($filePath, PATHINFO_EXTENSION)) {
                    case 'json': // collection
                        $fileContent = file_get_contents($filePath);
                        $obj = json_decode($fileContent,TRUE);
                        if (is_array($obj)) {
                            $collectionName = array_keys($obj)[0];
                            $dataAccessService->init($collectionName);
                            $restoredElements[$collectionName] = 0;

                            foreach ($obj[$collectionName]['data'] as $data) {
                                if (\MongoId::isValid($data['id'])) {
                                    $data['_id'] = new \MongoId($data['id']);
                                    unset($data['id']);
                                    switch ($restoreMode) {
                                        case 'INSERT':
                                            try {
                                                $dataAccessService->insert($data);
                                                $restoredElements[$collectionName]++;
                                            } catch (\Exception $e) {
                                                continue;
                                            }
                                            break;
                                        case 'UPSERT':
                                            try {
                                                $data['id'] = (string) $data['_id'];
                                                unset($data['_id']);
                                                $dataAccessService->update($data, ['upsert'=>TRUE]);
                                                echo 'Updated !' . PHP_EOL;
                                                $restoredElements[$collectionName]++;
                                            } catch (\Exception $e) {
                                                echo $e->getMessage();
                                                continue;
                                            }
                                            break;
                                    }
                                }
                            }
                        }
                        break;
                    default: // file
                        $buf = file_get_contents($filePath);
                        $originalFileId = substr($file,0,24);
                        $originalFileName = substr($file,25,strlen($file)-25);
                        $mainFileType = $fileCollectionService->getMainType($fileExtension);

                        $fileObj = array(
                            'bytes' => $buf,
                            'text' => $originalFileName,
                            'filename' => $originalFileName,
                            'Content-Type' => $fileExtension,
                            'mainFileType' => $mainFileType,
                            '_id' => new \MongoId($originalFileId)
                        );

                        switch ($restoreMode) {
                            case 'INSERT':
                                try {
                                    $fileService->createBinary($fileObj);
                                } catch (\Exception $e) {
                                    continue;
                                }
                                break;
                            case 'UPSERT':
                                try {
                                    $fileService->destroy(array(
                                        'id' => $originalFileId,
                                        'version' => 1
                                    ));
                                    $fileService->createBinary($fileObj);
                                    echo 'UPDATED ! '. PHP_EOL;
                                } catch (\Exception $e) {
                                    continue;
                                }
                                break;
                        }
                        break;
                }
            }
        }
    }

    public function getMigrationRepository()
    {
        if ($this->migrationRepository === null) {
            $this->migrationRepository = Manager::getService('inovia.collection.migration');
        }

        return $this->migrationRepository;
    }

    public function setServiceManager(\Zend\ServiceManager\ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

}