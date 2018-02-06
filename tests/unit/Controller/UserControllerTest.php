<?php

namespace DiskApp\Controller;

use Silex\Application;
use PHPUnit\Framework\TestCase;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderBag;

class UserControllerTest extends TestCase
{
    private $userController;
 
    protected function setUp()
    {
        $app = new Application();
        require __DIR__ . '/../../../src/app.php';

        $this->dbConnection = $app['db'];
        $this->userController = new UserController($app['users.service']);
    }
 
    protected function tearDown()
    {
        $this->deleteTestUsers();
        $this->dbConnection->close();
        $this->userController = null;
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

    public function testRegister()
    {
        $content = [
            'username' => 'petya',
            'password' => 'petya'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($content));
        $request->headers = new HeaderBag(['Content-Type' => 'application/json']);

        $actual = $this->userController->register($request);

        $expected = new JsonResponse(
            [
                'message' => 'user was successfully created!',
                'username' => 'petya'
            ], 
            Response::HTTP_CREATED
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRegisterDuplicateUser()
    {
        $this->insertTestUser('petya', hash('sha256', 'petya' . UserController::SALT, false));

        $content = [
            'username' => 'petya',
            'password' => 'petya'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($content));
        $request->headers = new HeaderBag(['Content-Type' => 'application/json']);

        $actual = $this->userController->register($request);

        $expected = new JsonResponse(
            [
                'message' => 'user with that username already exists!'
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRegisterNoUsername()
    {
        $content = [
            'username' => '',
            'password' => ''
        ];

        $request = new Request([], [], [], [], [], [], json_encode($content));
        $request->headers = new HeaderBag(['Content-Type' => 'application/json']);

        $actual = $this->userController->register($request);

        $expected = new JsonResponse(
            [
                'message' => 'user can not be added because his username is not specified!'
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $this->assertEquals($expected, $actual);
    }
}