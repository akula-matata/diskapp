<?php

namespace DiskApp\Model;

class User
{
    private $id;
    private $username;
    private $hash;

    public function __construct($id, $username, $hash)
    {
        $this->id = $id;
        $this->username = $username;
        $this->hash = $hash;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getHash()
    {
        return $this->hash;
    }

}