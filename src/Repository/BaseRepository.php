<?php

namespace DiskApp\Repository;

use Doctrine\DBAL\Connection;

class BaseRepository
{
    protected $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
}