<?php

namespace GoogleApiBundle\DataProvider;

use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class GoogleDriveDataProvider
{
    const SEARCH_FILES = 'search_files';
    const SEARCH_FOLDERS = 'search_folders';

    /** @var Google_Service_Drive */
    private $driveService;

    public function __construct(Google_Service_Drive $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * @return \Generator|Google_Service_Drive_DriveFile
     */
    public function getFiles(ParamBuilder $paramBuilder)
    {
        $pageToken = null;
        $params = $paramBuilder->getParams();
        do {
            try {

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $results = $this->driveService->files->listFiles($params);

                foreach ($results->getFiles() as $file) {
                    yield $file;
                }

                $pageToken = $results->nextPageToken;

            } catch (\Exception $e) {
                print "An error occurred: ".$e->getMessage();
                $pageToken = null;
            }
        } while ($pageToken);
    }

    public function createFolder($name, $folderId = null): string
    {
        $fileMetadata = new Google_Service_Drive_DriveFile(
            [
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$folderId],
            ]
        );

        return $this->driveService->files->create($fileMetadata, ['fields' => 'id'])->id;
    }

    public function move(Google_Service_Drive_DriveFile $file, $folderId, $distFolderId): Google_Service_Drive_DriveFile
    {
        $emptyFileMetadata = new Google_Service_Drive_DriveFile();
        return $this->driveService->files->update(
            $file->id,
            $emptyFileMetadata,
            [
                'addParents' => $distFolderId,
                'removeParents' => $folderId,
                'fields' => 'id, parents',
            ]
        );
    }
}
