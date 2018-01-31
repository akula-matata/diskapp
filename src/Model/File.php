<?php

namespace DiskApp\Model;

class File
{
    private $id;
    private $filename;
    private $user;

    public function __construct($filename, User $user)
    {
        $this->filename = $filename;
        $this->user = $user;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getUser()
    {
        return $this->user;
    }
}