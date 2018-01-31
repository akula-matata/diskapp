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

    public function createUser($login, $hash)
    {
        try
        {
            $user = new User($login, $hash);
            $user = $this->users->add($user);
        }
        catch (Exception $ex)
        {
            throw new UserServiceException($ex->getMessage());
        }
    }

    public function getUserByLogin($login)
    {
        try
        {
            $user = $this->users->getUserByLogin($login);
        }
        catch (Exception $ex)
        {
            throw new UserServiceException($ex->getMessage());
        }
        
        return $user;
    }
}