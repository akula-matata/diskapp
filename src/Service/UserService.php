<?php

namespace DiskApp\Service;

use Exception;
use DiskApp\Model\User;
use DiskApp\Repository\UserRepository;

class UserServiceException extends Exception { }

class UserService
{
    private $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function createUser($username, $hash)
    {
        try
        {
            $user = new User(null, $username, $hash);
            $user = $this->users->add($user);
        }
        catch (Exception $ex)
        {
            throw new UserServiceException($ex->getMessage());
        }
    }

    public function getByUsername($username)
    {
        try
        {
            $user = $this->users->getByUsername($username);
        }
        catch (Exception $ex)
        {
            throw new UserServiceException($ex->getMessage());
        }
        
        return $user;
    }
}