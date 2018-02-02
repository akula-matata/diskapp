<?php

namespace DiskApp\Controller;

use Exception;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use DiskApp\Service\UserService;

class BaseControllerException extends Exception { }

class BaseController
{
    const SALT = 'diskapp';

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function parseJSON(Request $request)
    {
        if ($request->headers->get('Content-Type') == 'application/json')
        {
            $data = json_decode($request->getContent(), true);
            return $data;
        }
        else
        {
            return array();
        }
    }

    protected function checkAuthenticationData($username, $password)
    {
         $hash = hash('sha256', $password . self::SALT, false);

        try
        {
            $user = $this->userService->getUserByUsername($username);
        }
        catch (Exception $ex)
        {
            throw new BaseControllerException($ex->getMessage());
        }

        if(!isset($user) OR $user->getHash() != $hash)
        {
            throw new Exception('user not found or password incorrect!', 1);
        }
    }
}