<?php

namespace DiskApp\Controller;

use Exception;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use DiskApp\Service\UserService;

class UserController extends BaseController
{

    public function register(Request $request)
    {
        try
        {
            $json = $this->parseJSON($request);

            $login = $json["login"];
            $password = $json["password"];

            $hash = hash('sha256', $password . self::SALT, false);

            $this->userService->createUser($login, $hash);

            return new JsonResponse(['message' => 'user was successfully created!'], Response::HTTP_CREATED);
        }
        catch(Exception $ex)
        {
            return new JsonResponse(['message' => $ex->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}