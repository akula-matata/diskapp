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

        $this->dbConnection->executeQuery(
            'INSERT INTO users (username, hash) VALUES (?, ?)', 
            [
                'petya',
                hash('sha256', 'petya' . BaseController::SALT, false)
            ]
        );

        $this->lastId = $this->dbConnection->lastInsertId();

    }

    protected function tearDown()
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM users WHERE username in (?)',
            [
                'petya'
            ]
        );

        $this->files = null;
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAdd($file, $expected, $exception)
    {
        if ($exception)
        {
            $this->expectException($exception);
        }

        $actual = $this->files->add($file);

        $this->assertEquals($actual, $expected);
    }

    public function addDataProvider()
    {
    	return [
            [new File(null, 'file.txt', new User(null, 'petya', hash('sha256', 'petya' . BaseController::SALT, false))), null, null]
        ];
    }
}