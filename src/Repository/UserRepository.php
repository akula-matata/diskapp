<?php

namespace DiskApp\Repository;

use Exception;

use DiskApp\Model\User;

class UserRepositoryException extends Exception { }

class UserRepository extends BaseRepository
{
    public function add(User $user)
    {
        try
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO users (login, hash) VALUES (?, ?)',
                [
                    $user->getLogin(), 
                    $user->getHash()
                ]
            );
        }
        catch(DBALException $ex)
        {
            throw new UserRepositoryException($ex->getMessage());
        }
    }

    public function getUserByLogin($login)
    {
        try
        {
            $statement = $this->dbConnection->executeQuery(
                'SELECT id, login, hash FROM users WHERE login = ?',
                [
                    $login
                ]
            );

            $executeQuery = $statement->fetch();

            $user = new User($executeQuery["login"], $executeQuery["hash"]);
            $user->setId($executeQuery["id"]);

            return $user;
        }
        catch(DBALException $ex)
        {
            throw new UserRepositoryException($ex->getMessage());
        }
    }

}