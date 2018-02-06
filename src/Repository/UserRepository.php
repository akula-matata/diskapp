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
            if (empty($user->getUsername()))
            {
                throw new UserRepositoryException('user can not be added because his username is not specified!');
            }

            $statement = $this->dbConnection->executeQuery(
                'SELECT username FROM users WHERE username = ?',
                [
                    $user->getUsername()
                ]
            );
            $result = $statement->fetch();

            if ($result != false)
            {
                throw new UserRepositoryException('user with that username already exists!');
            }

            $this->dbConnection->executeQuery(
                'INSERT INTO users (username, hash) VALUES (?, ?)',
                [
                    $user->getUsername(), 
                    $user->getHash()
                ]
            );
        }
        catch(Exception $ex)
        {
            throw new UserRepositoryException($ex->getMessage());
        }
    }

    public function getByUsername($username)
    {
        try
        {
            $statement = $this->dbConnection->executeQuery(
                'SELECT id, username, hash FROM users WHERE username = ?',
                [
                    $username
                ]
            );
            $result = $statement->fetch();

            if ($result == false)
            {
                throw new UserRepositoryException('user with this username does not exist!');
            }

            $user = new User($result["id"], $result["username"], $result["hash"]);

            return $user;
        }
        catch(Exception $ex)
        {
            throw new UserRepositoryException($ex->getMessage());
        }
    }

}