<?php

namespace DiskApp\Repository;

use Silex\Application;
use PHPUnit\Framework\TestCase;

use DiskApp\Controller\BaseController;
use DiskApp\Model\User;

class UserRepositoryTest extends TestCase
{
    private $users;
    
    private $dbConnection;
 
    protected function setUp()
    {
        $app = new Application();
        require __DIR__ . '/../../../src/app.php';

        $this->dbConnection = $app['db'];

        $this->users = new UserRepository($this->dbConnection);

        $this->dbConnection->executeQuery(
            'INSERT INTO users (username, hash) VALUES (?, ?)', 
            [
                'sasha',
                hash('sha256', 'sasha' . BaseController::SALT, false)
            ]
        );
    }
 
    protected function tearDown()
    {
        $this->dbConnection->executeQuery('
            DELETE FROM users WHERE username in (?, ?)',
            [
                'petya',
                'sasha'
            ]
        );

        $this->users = null;
    }
 
    /**
     * @dataProvider addDataProvider
     */
    public function testAdd($user, $expected, $exception)
    {
        if ($exception)
        {
            $this->expectException($exception);
        }

        $actual = $this->users->add($user);

        $this->assertEquals($actual, $expected);
    }

    public function addDataProvider()
    {
        return [
            [new User(null, 'petya', hash('sha256', 'petya' . BaseController::SALT, false)), null, null],
            [new User(null, 'sasha', hash('sha256', 'sasha' . BaseController::SALT, false)), null, UserRepositoryException::class]
        ];
    }

    /**
     * @dataProvider getUserByUsernameDataProvider
     */
    public function testGetUserByUsername($username, $expected, $exception)
    {
        if ($exception)
        {
            $this->expectException($exception);
        }

        $actual = $this->users->getUserByUsername($username);

        $this->assertEquals($actual->getUsername(), $expected->getUsername());
    }

    public function getUserByUsernameDataProvider()
    {
        return [
            ['petya', null, UserRepositoryException::class],
            ['sasha', new User(null, 'sasha', hash('sha256', 'sasha' . BaseController::SALT, false)), null]
        ];
    }
}