<?php

namespace GoogleApiBundle\DataProvider;

use Google_Client;
use Google_Service_Drive;
use Symfony\Component\HttpKernel\KernelInterface;

class ClientDataProvider
{
    /** @var KernelInterface */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @throws \Google_Exception
     */
    public function getDriveDataProvider(): GoogleDriveDataProvider
    {
        $client = $this->getClient();
        $service = new Google_Service_Drive($client);

        return new GoogleDriveDataProvider($service);
    }

    /**
     * @throws \Google_Exception
     */
    public function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Drive API PHP Quickstart');
        $client->setScopes([Google_Service_Drive::DRIVE]);
        $client->setAuthConfig($this->kernel->getRootDir().'/config/credentials/client_secret.json');
        $client->setAccessType('offline');
        $client->setRedirectUri('http://'.'localhost'.'/oauth2callback.php');
        $client->setApprovalPrompt('force');

        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory('credentials.json');
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }

        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    protected function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE').getenv('HOMEPATH');
        }

        return str_replace('~', realpath($homeDirectory), $path);
    }
}

