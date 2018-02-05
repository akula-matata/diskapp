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
    private $lastId;

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
    }

    public function deleteTestFiles()
    {
        $this->dbConnection->executeQuery('DELETE FROM files');
    }

    public function deleteTestUsers()
    {
        $this->dbConnection->executeQuery('DELETE FROM users');
    }

    public function testAdd()
    {
        $this->lastId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));

        $user = new User($this->lastId, 'petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $file = new File(null, 'file.txt', $user);

        $actual = $this->files->add($file);

        $this->assertEquals($actual, null);
    }

    public function testAddDuplicateFile()
    {
        if (FileRepositoryException::class)
        {
            $this->expectException(FileRepositoryException::class);
        }

    	$this->lastId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $this->insertTestFile('file.txt', $this->lastId);

        $user = new User($this->lastId, 'petya', hash('sha256', 'petya' . BaseController::SALT, false));
        $file = new File(null, 'file.txt', $user);

        $actual = $this->files->add($file); 
    }

    public function testAddNoUser()
    {
    	if (FileRepositoryException::class)
        {
            $this->expectException(FileRepositoryException::class);
        }

    	$this->lastId = $this->insertTestUser('petya', hash('sha256', 'petya' . BaseController::SALT, false));

        $user = new User(999, 'sasha', hash('sha256', 'sasha' . BaseController::SALT, false));
        $file = new File(null, 'file.txt', $user);

        $actual = $this->files->add($file);
    }
}