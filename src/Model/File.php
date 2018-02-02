<?php

namespace DiskApp\Model;

class File
{
    private $id;
    private $filename;
    private $user;

    public function __construct($id, $filename, User $user)
    {
        $this->id = $id;
        $this->filename = $filename;
        $this->user = $user;
    }

    public function getId()
    {
        return $this->id;
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