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

    public function getUserByUsername($username)
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
                throw new UserRepositoryException('no user with such username!');
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