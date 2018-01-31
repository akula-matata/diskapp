<?php

namespace DiskApp\Repository;

use Doctrine\DBAL\Connection;

abstract class BaseRepository
{
    protected $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
}