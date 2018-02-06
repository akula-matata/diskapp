<?php

namespace DiskApp\Controller;

use Silex\Application;
use PHPUnit\Framework\TestCase;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileControllerTest extends TestCase
{
    const UPLOAD_DIRECTORY = "\\..\\..\\..\\web\\upload\\";
    const UPLOAD_TEST_DIRECTORY = "\\..\\Service\\upload_test\\";

    private $fileController;
 
    protected function setUp()
    {
        $app = new Application();
        require __DIR__ . '/../../../src/app.php';

        $this->dbConnection = $app['db'];
        $this->fileController = new FileController($app['users.service'], $app['files.service']);
    }
 
    protected function tearDown()
    {
        $this->deleteTestFiles();
        $this->deleteTestUsers();
        $this->dbConnection->close();

        if (file_exists(__DIR__ . self::UPLOAD_DIRECTORY))
        {
            foreach (glob(__DIR__ . self::UPLOAD_DIRECTORY . '*') as $file) 
            {
                unlink($file);
            }
        }

        $this->fileController = null;
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

    public function testPutFile()
    {
        $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
        copy($originalFile, $tempFile);

        $file = new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [ 'file' => $file ], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->putFile($request);

        $expected = new JsonResponse(
            [
                'message' => 'file was successfully created!',
                'username' => 'petya',
                'filename' => 'uploaded_file.txt'
            ], 
            Response::HTTP_CREATED
        );

        $this->assertEquals($expected, $actual);
    }

    public function testPutFileDuplicateFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file.txt';
        copy($originalFile, $tempFile);

        $file = new UploadedFile($tempFile, 'uploaded_file.txt', null, null, null, true);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [ 'file' => $file ], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->putFile($request);

        $expected = new JsonResponse(
            [
                'message' => 'can not add this file from the specified user!'
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $this->assertEquals($expected, $actual);
    }

    public function testDeleteFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt';
        copy($originalFile, $tempFile);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->deleteFile($request, 'uploaded_file.txt');

        $expected = new JsonResponse(
            [
                'message' => 'file was successfully deleted!'
            ], 
            Response::HTTP_OK
        );

        $this->assertEquals($expected, $actual);
    }

    public function testDeleteFileNoFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt';
        copy($originalFile, $tempFile);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->deleteFile($request, 'not_existing_file.txt');

        $expected = new JsonResponse(
            [
                'message' => 'the user can not get access to file with that filename!'
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $this->assertEquals($expected, $actual);
    }

    public function testUpdateFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt';
        copy($originalFile, $tempFile);

        $originalFileForUpdate = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file_for_update.txt';
        $tempFileForUpdate = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file_for_update.txt';
        copy($originalFileForUpdate, $tempFileForUpdate);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [ 'file' => new UploadedFile($tempFileForUpdate, 'uploaded_file.txt', null, null, null, true) ], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->updateFile($request);

        $expected = new JsonResponse(
            [
                'message' => 'file was successfully updated!'
            ], 
            Response::HTTP_OK
        );

        $this->assertEquals($expected, $actual);
    }

    public function testUpdateFileNoFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt';
        copy($originalFile, $tempFile);

        $originalFileForUpdate = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file_for_update.txt';
        $tempFileForUpdate = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'temp_file_for_update.txt';
        copy($originalFileForUpdate, $tempFileForUpdate);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [ 'file' => new UploadedFile($tempFileForUpdate, 'not_existing_file.txt', null, null, null, true) ], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->updateFile($request);

        $expected = new JsonResponse(
            [
                'message' => 'the user can not get access to file with that filename!'
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $this->assertEquals($expected, $actual);
    }

    public function testGetFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt';
        copy($originalFile, $tempFile);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->getFile($request, 'uploaded_file.txt');

        $expected = new BinaryFileResponse($tempFile);
        $expected->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'uploaded_file.txt');

        $this->assertEquals($expected, $actual);
    }

    public function testGetFileNoFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt';
        copy($originalFile, $tempFile);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->getFile($request, 'not_existing_file.txt');

        $expected = new JsonResponse(
            [
                'message' => 'the user can not get access to file with that filename!'
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilesList()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('petya_file.txt', $userId);
        $this->insertTestFile('another_petya_file.txt', $userId);

        $userId = $this->insertTestUser('sasha', hash('sha256', 'sasha' . FileController::SALT, false));
        $this->insertTestFile('sasha_file.txt', $userId);
        $this->insertTestFile('another_sasha_file.txt', $userId);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->getFilesList($request);

        $expected = new JsonResponse(
            [
                [
                    'filename' => 'petya_file.txt',
                    'username' => 'petya'
                ],
                [
                    'filename' => 'another_petya_file.txt',
                    'username' => 'petya'
                ],
                [
                    'filename' => 'sasha_file.txt',
                    'username' => 'sasha'
                ],
                [
                    'filename' => 'another_sasha_file.txt',
                    'username' => 'sasha'
                ]
            ], 
            Response::HTTP_OK
        );

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilesListEmpty()
    {
        $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestUser('sasha', hash('sha256', 'sasha' . FileController::SALT, false));

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->getFilesList($request);

        $expected = new JsonResponse(
            [
                'message' => 'file repository is empty!'
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $this->assertEquals($expected, $actual);
    }

    public function testGetFileMetadata()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt';
        copy($originalFile, $tempFile);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->getFileMetadata($request, 'uploaded_file.txt');

        $expected = new JsonResponse(
            [
                'filename' => 'uploaded_file.txt',
                'type' => filetype($tempFile),
                'mime_type' => mime_content_type($tempFile),
                'size' => filesize($tempFile),
                'modified' => date ("F d Y H:i:s.", filemtime($tempFile))
            ],
            Response::HTTP_OK
        );

        $this->assertEquals($expected, $actual);
    }

    public function testGetFileMetadataNoFile()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . FileController::SALT, false));
        $this->insertTestFile('uploaded_file.txt', $userId);

        $originalFile = __DIR__ . self::UPLOAD_TEST_DIRECTORY . 'original_file.txt';
        $tempFile = __DIR__ . self::UPLOAD_DIRECTORY . 'uploaded_file.txt';
        copy($originalFile, $tempFile);

        $request = new Request(
            [], 
            [], 
            [], 
            [], 
            [], 
            [ 'PHP_AUTH_USER' => 'petya', 'PHP_AUTH_PW' => 'petya' ], 
            []
        );

        $actual = $this->fileController->getFileMetadata($request, 'not_existing_file.txt');

        $expected = new JsonResponse(
            [
                'message' => 'file with this filename does not exist!',
            ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $this->assertEquals($expected, $actual);
    }
}