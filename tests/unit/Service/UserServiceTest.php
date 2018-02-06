<?php

namespace DiskApp\Service;

use Silex\Application;
use PHPUnit\Framework\TestCase;

use DiskApp\Controller\UserController;
use DiskApp\Model\User;

class UserServiceTest extends TestCase
{
    private $dbConnection;
    private $userService;
 
    protected function setUp()
    {
        $app = new Application();
        require __DIR__ . '/../../../src/app.php';

        $this->dbConnection = $app['db'];
        $this->userService = new UserService($app['users.repository']);
    }
 
    protected function tearDown()
    {
        $this->deleteTestUsers();
        $this->dbConnection->close();
        $this->userService = null;
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

    public function testCreateUser()
    {
        $actual = $this->userService->createUser('petya', hash('sha256', 'petya' . UserController::SALT, false));
        $this->assertEquals(null, $actual);
    }

    public function testCreateUserDuplicateUser()
    {
        try
        {
            $this->insertTestUser('petya', hash('sha256', 'petya' . UserController::SALT, false));
            $actual = $this->userService->createUser('petya', hash('sha256', 'petya' . UserController::SALT, false));
        }
        catch (UserServiceException $ex)
        {
            $this->assertEquals('user with that username already exists!', $ex->getMessage());
        }
    }

    public function testGetByUsername()
    {
        $this->insertTestUser('petya', hash('sha256', 'petya' . UserController::SALT, false));
        $actual = $this->userService->getByUsername('petya');

        $this->assertEquals('petya', $actual->getUsername());
    }

    public function testGetByUsernameNoUser()
    {
        try
        {
            $this->insertTestUser('petya', hash('sha256', 'petya' . UserController::SALT, false));
            $actual = $this->userService->getByUsername('sasha');
        }
        catch (UserServiceException $ex)
        {
            $this->assertEquals('user with this username does not exist!', $ex->getMessage());
        }
    }
}