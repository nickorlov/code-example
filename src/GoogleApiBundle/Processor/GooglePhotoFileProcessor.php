<?php

namespace GoogleApiBundle\Processor;

use Google_Service_Drive_DriveFile;
use GoogleApiBundle\DataProvider\ClientDataProvider;
use GoogleApiBundle\DataProvider\GoogleDriveDataProvider;
use GoogleApiBundle\DataProvider\ParamBuilder;

class GooglePhotoFileProcessor
{
    /** @var ClientDataProvider */
    protected $clientDataProvider;

    /** @var GoogleDriveDataProvider */
    protected $drive;

    public function __construct(ClientDataProvider $clientDataProvider)
    {
        $this->clientDataProvider = $clientDataProvider;
    }

    public function initDrive()
    {
        $this->drive = $this->clientDataProvider->getDriveDataProvider();
    }

    public function process()
    {
        $this->initDrive();
        $params = (new ParamBuilder())->setFoldersOnly()->setSearchByName('Google Фото');
        $googlePhotoId = $this->drive->getFiles($params)->current()->getId();
//        $this->createMonthFolder($googlePhotoId, 2017,03);
        $params = (new ParamBuilder())
            ->setFilesOnly()
            ->setOrderBy('createdTime')
            ->addSelectFields(['parents'])
            ->setFolderId($googlePhotoId);

        $files = $this->drive->getFiles($params);

        do {
            $batchFiles = [];
            for ($i = 0; $i < 1000; $i++) {
                $file = $files->current();
                if (!$file) {
                    break;
                }
                $batchFiles[] = $file;
                $files->next();
            }
            $groupedFiles = $this->groupFiles($batchFiles);
            $this->moveFiles($googlePhotoId, $groupedFiles);

        } while ($file);
    }

    protected function moveFiles(string $googlePhotoId, array $groupedFiles): void{
        foreach ($groupedFiles as $year => $groupedByMonthFiles){
            foreach ($groupedByMonthFiles as $month => $files){
                $folderId = $this->createMonthFolder($googlePhotoId, $year, $month);
                foreach ($files as $file){
                    echo $file->name . ' ---> '. $year.'-'.$month. ' moving ...          ';
                    $this->drive->move($file, $googlePhotoId, $folderId);
                }
            }
        }
    }
    /**
     * @param Google_Service_Drive_DriveFile[] $files
     * @return array|Google_Service_Drive_DriveFile[]
     */
    protected function groupFiles(array $files){
        $result = [];
        foreach ($files as $file){
            $date = new \DateTime($file->getCreatedTime());
            $result[$date->format('Y')][$date->format('m')][] = $file;
        }

        return $result;
    }

    protected function createMonthFolder($googleFolderId, $year, $month, $force = false): string
    {
        $params = (new ParamBuilder())
            ->setSearchByName($year)
            ->setFoldersOnly()
            ->setLimit(1)
            ->setFolderId($googleFolderId);

        $yearFolder = $this->drive->getFiles($params)->current();

        $yearFolderId = ($yearFolder && !$force) ? $yearFolder->getId() : $this->drive->createFolder($year, $googleFolderId);

        $params = (new ParamBuilder())
            ->setSearchByName($month)
            ->setFoldersOnly()
            ->setLimit(1)
            ->setFolderId($yearFolderId);

        $monthFolder = $this->drive->getFiles($params)->current();

        return $monthFolder ? $monthFolder->getId() : $this->drive->createFolder($month, $yearFolderId);
    }
}
