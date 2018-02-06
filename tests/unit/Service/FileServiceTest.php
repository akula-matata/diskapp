<?php

namespace DiskApp\Service;

use Silex\Application;
use PHPUnit\Framework\TestCase;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use DiskApp\Controller\BaseController;
use DiskApp\Model\User;
use DiskApp\Model\File;

class FileServiceTest extends TestCase
{
    const UPLOAD_DIRECTORY = "\\..\\..\\..\\web\\upload\\";
    const UPLOAD_TEST_DIRECTORY = "\\upload_test\\";

    private $dbConnection;
    private $fileService;  

    protected function setUp()
    {
        $app = new Application();
        require __DIR__ . '/../../../src/app.php';

        $this->dbConnection = $app['db'];
        $this->fileService = new FileService($app['users.repository'], $app['files.repository']);
    }
 
    protected function tearDown()
    {
        $this->deleteTestFiles();
        $this->deleteTestUsers();

        if (file_exists(__DIR__ . self::UPLOAD_DIRECTORY))
        {
            foreach (glob(__DIR__ . self::UPLOAD_DIRECTORY . '*') as $file) 
            {
                unlink($file);
            }
        }

        $this->dbConnection->close();
        $this->fileService = null;
    }

    public function insertTestUser($username, $hash)
    {
        $this->dbConnection->executeQuery(
            'INSERT INTO users (username, hash) VALUES (?, ?)', 
            [
                $username, $hash
            ]
        );

        return $this->dbConnection->lastInsertId();
    }

    public function insertTestFile($filename, $user_id)
    {
        $this->dbConnection->executeQuery(
            'INSERT INTO files (filename, user_id) VALUES (?, ?)', 
            [
                $filename, $user_id
            ]
        );

        return $this->dbConnection->lastInsertId();
    }

    public function deleteTestUsers()
    {
        $this->dbConnection->executeQuery('DELETE FROM users');
    }

    public function deleteTestFiles()
    {
        $this->dbConnection->executeQuery('DELETE FROM files');
    }

    public function testCreateFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
        copy($originalFile, $tempFile);
        $fileContent = new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true);

        $actual = $this->fileService->createFile('petya', 'file.txt', $fileContent);

        $this->assertEquals(null, $actual);
    }

    public function testCreateFileDuplicateFile()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $this->insertTestFile('file.txt', $userId);

            $actual = $this->fileService->createFile('petya', 'file.txt', null);
        }
        catch (FileServiceException $ex)
        {
            $this->assertEquals('can not add this file from the specified user!', $ex->getMessage());
        }
    }

    public function testSaveFileContent()
    {
        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
        copy($originalFile, $tempFile);
        $fileContent = new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true);

        $actual = $this->fileService->saveFileContent($fileContent);

        $this->assertEquals(null, $actual);
    }

    public function testSaveFileContentNoContent()
    {
        try
        {
            $actual = $this->fileService->saveFileContent(null);
        }
        catch (FileServiceException $ex)
        {
            $this->assertEquals('no content found among uploaded files!', $ex->getMessage());
        }
    }

    public function testGetFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
        copy($originalFile, $tempFile);
        $this->fileService->saveFileContent(new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true));

        $actual = $this->fileService->getFile('uploaded_file.txt');

        $this->assertEquals(realpath(__DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt'), realpath($actual));
    }

    public function testGetFileNoFile()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $this->insertTestFile('uploaded_file.txt', $userId);

            $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
            $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
            copy($originalFile, $tempFile);
            $this->fileService->saveFileContent(new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true));

            $actual = $this->fileService->getFile('not_existing_file.txt');
        }
        catch (FileServiceException $ex)
        {
            $this->assertEquals('the user can not get access to file with that filename!', $ex->getMessage());
        }
    }

    public function testGetFilesList()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $this->insertTestFile('file.txt', $userId);

        $userId = $this->insertTestUser('sasha', hash('sha256', 'sasha' . BaseController::SALT, false));
        $this->insertTestFile('another_file.txt', $userId);

        $actual = $this->fileService->getFilesList();

        $expected = [
            [
                'filename' => 'file.txt', 
                'username' => 'petya'
            ],
            [
                'filename' => 'another_file.txt', 
                'username' => 'sasha'
            ]
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilesListEmpty()
    {
        try
        {
            $actual = $this->fileService->getFilesList();
        }
        catch (FileServiceException $ex)
        {
            $this->assertEquals('file repository is empty!', $ex->getMessage());
        }
    }

    public function testDeleteFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $this->insertTestFile('file.txt', $userId);

        $actual = $this->fileService->deleteFile('petya', 'file.txt');

        $this->assertEquals(null, $actual);
    }

    public function testDeleteFileNoFile()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $this->insertTestFile('file.txt', $userId);

            $userId = $this->insertTestUser('sasha', hash('sha256', 'sasha' . BaseController::SALT, false));
            $this->insertTestFile('another_file.txt', $userId);

            $actual = $this->fileService->deleteFile('petya', 'another_file.txt');
        }
        catch (FileServiceException $ex)
        {
            $this->assertEquals('the user can not get access to file with that filename!', $ex->getMessage());
        }
    }

    public function testUpdateFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $this->insertTestFile('file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
        copy($originalFile, $tempFile);
        $this->fileService->saveFileContent(new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true));

        $originalFileForUpdate = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file_for_update.txt';
        $tempFileForUpdate = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file_for_update.txt';
        copy($originalFileForUpdate, $tempFileForUpdate);
        $fileContentForUpdate = new UploadedFile($tempFileForUpdate, 'uploaded_file.txt', null, null, null, true);
        
        $actual = $this->fileService->updateFile('petya', 'file.txt', $fileContentForUpdate);

        $this->assertEquals(null, $actual);
    }

    public function testUpdateFileNoFile()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $this->insertTestFile('file.txt', $userId);

            $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
            $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
            copy($originalFile, $tempFile);
            $this->fileService->saveFileContent(new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true));

            $actual = $this->fileService->updateFile('petya', 'not_existing_file.txt', $fileContentForUpdate);
        }
        catch (FileServiceException $ex)
        {
            $this->assertEquals('the user can not get access to file with that filename!', $ex->getMessage());
        }
    }

    public function testGetFileMetadata()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
        copy($originalFile, $tempFile);
        $this->fileService->saveFileContent(new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true));
        
        $actual = $this->fileService->getFileMetadata('uploaded_file.txt');

        $path = realpath(__DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt');
        $expected = [
            'filename' => 'uploaded_file.txt',
            'type' => filetype($path),
            'mime_type' => mime_content_type($path),
            'size' => filesize($path),
            'modified' => date ("F d Y H:i:s.", filemtime($path))
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testGetFileMetadataNoFile()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $this->insertTestFile('uploaded_file.txt', $userId);

            $actual = $this->fileService->getFileMetadata('not_existing_file.txt');
        }
        catch (FileServiceException $ex)
        {
            $this->assertEquals('file with this filename does not exist!', $ex->getMessage());
        }
    }
}