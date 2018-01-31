<?php

namespace DiskApp\Model;

class User
{
    private $id;
    private $login;
    private $hash;

    public function __construct($login, $hash)
    {
        $this->login = $login;
        $this->hash = $hash;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getHash()
    {
        return $this->hash;
    }

}