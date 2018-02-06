<?php

namespace DiskApp\Repository;

use Silex\Application;
use PHPUnit\Framework\TestCase;

use DiskApp\Controller\BaseController;
use DiskApp\Model\User;
use DiskApp\Model\File;

class FileRepositoryTest extends TestCase
{
    private $files;
    private $dbConnection;

    protected function setUp()
    {
        $app = new Application();
        require __DIR__ . '/../../../src/app.php';

        $this->dbConnection = $app['db'];
        $this->files = new FileRepository($this->dbConnection);
    }

    protected function tearDown()
    {
        $this->deleteTestFiles();
        $this->deleteTestUsers();
        $this->dbConnection->close();
        $this->files = null;
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

    public function testAdd()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $user = new User($userId, 'petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $file = new File(null, 'file.txt', $user);
        $actual = $this->files->add($file);

        $this->assertEquals(null, $actual);
    }

    public function testAddDuplicateFile()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $fileId = $this->insertTestFile('file.txt', $userId);

            $user = new User($userId, 'petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $file = new File(null, 'file.txt', $user);

            $actual = $this->files->add($file); 
        }
        catch (FileRepositoryException $ex)
        {
            $this->assertEquals('can not add this file from the specified user!', $ex->getMessage());
        }
    }

    public function testAddNoUser()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $user = new User(999, 'sasha', hash('sha256', 'sasha' . BaseController::SALT, false));
            $file = new File(null, 'file.txt', $user);

            $actual = $this->files->add($file);
        }
        catch (FileRepositoryException $ex)
        {
            $this->assertEquals('can not add this file from the specified user!', $ex->getMessage());
        }
    }

    public function testGetByFilename()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $fileId = $this->insertTestFile('file.txt', $userId);
        $actual = $this->files->getByFilename('file.txt');
        
        $expected = new File($fileId, 'file.txt', 
            new User($userId, 'petya', hash('sha256', 'petya' . BaseController::SALT, false))
        );

        $this->assertEquals($expected, $actual);
    }

    public function testGetByFilenameNoFile()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $fileId = $this->insertTestFile('file.txt', $userId);
            $actual = $this->files->getByFilename('another_file.txt');
        }
        catch (FileRepositoryException $ex)
        {
            $this->assertEquals('the user can not get access to file with that filename!', $ex->getMessage());
        }
    }

    public function testRemove()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $fileId = $this->insertTestFile('file.txt', $userId);

        $user = new User($userId, 'petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $file = new File($fileId, 'file.txt', $user);

        $actual = $this->files->remove($user, $file);

        $this->assertEquals(null, $actual);
    }

    public function testRemoveNoFile()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $fileId = $this->insertTestFile('file.txt', $userId);

            $user = new User($userId, 'petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $file = new File($fileId, 'another_file.txt', $user);

            $actual = $this->files->remove($user, $file);
        }
        catch (FileRepositoryException $ex)
        {
            $this->assertEquals('there is no such file that can be deleted by this user!', $ex->getMessage());
        }
    }

    public function testRemoveByAnotherUser()
    {
        try
        {
            $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
            $fileId = $this->insertTestFile('file.txt', $userId);

            $anotherUserId = $this->insertTestUser('sasha', hash('sha256', 'sasha' . BaseController::SALT, false));

            $user = new User($anotherUserId, 'sasha', hash('sha256', 'sasha' . BaseController::SALT, false));
            $file = new File($fileId, 'file.txt', $user);

            $actual = $this->files->remove($user, $file);
        }
        catch (FileRepositoryException $ex)
        {
            $this->assertEquals('there is no such file that can be deleted by this user!', $ex->getMessage());
        }
    }

    public function testGetFilesList()
    {
        $userId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $this->insertTestFile('file.txt', $userId);

        $userId = $this->insertTestUser('sasha', hash('sha256', 'sasha' . BaseController::SALT, false));
        $this->insertTestFile('another_file.txt', $userId);

        $actual = $this->files->getFilesList();

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
            $actual = $this->files->getFilesList();
        }
        catch (FileRepositoryException $ex)
        {
            $this->assertEquals('file repository is empty!', $ex->getMessage());
        }
    }
}