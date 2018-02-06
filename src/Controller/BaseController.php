<?php

namespace DiskApp\Controller;

use DiskApp\Service\UserService;

class BaseController
{
    const SALT = 'diskapp';

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
}