<?php

namespace DiskApp\Service;

use Exception;
use DiskApp\Model\User;
use DiskApp\Model\File;
use DiskApp\Repository\UserRepository;
use DiskApp\Repository\FileRepository;

class FileServiceException extends Exception { }

class FileService
{
    const UPLOAD_DIRECTORY = "\\..\\..\\web\\upload\\";

    private $users;
    private $files;

    public function __construct(UserRepository $users, FileRepository $files)
    {
        $this->users = $users;
        $this->files = $files;
    }

    public function getFilesList()
    {
        try
        {
            return $this->files->getFilesList();
        }
        catch (Exception $ex)
        {
            throw new FileServiceException($ex->getMessage());
        }
    }

    public function getFile($filename)
    {
        $path = __DIR__ . self::UPLOAD_DIRECTORY . $filename;

        if (!file_exists($path))
        {
            throw new FileServiceException('file with this filename does not exist!');
        }

        return $path;
    }

    public function createFile($username, $filename, $fileContent)
    {
        try
        {
            $user = $this->users->getByUsername($username);

            $file = new File(null, $filename, $user);

            $this->files->add($file);
            $this->saveFileContent($fileContent);
        }
        catch (Exception $ex)
        {
            throw new FileServiceException($ex->getMessage());
        }
    }

    public function saveFileContent($fileContent)
    {
        try
        {
            if (empty($fileContent))
            {
                throw new FileServiceException('no content found among uploaded files!');
            }

            $filename = $fileContent->getClientOriginalName();
            $fileContent->move(__DIR__ . self::UPLOAD_DIRECTORY, $filename);
        }
        catch (Exception $ex)
        {
            throw new FileServiceException($ex->getMessage());
        }
    }

    public function deleteFile($username, $filename)
    {
        try
        {
            $user = $this->users->getByUsername($username);
            $file = $this->files->getByFilename($filename);

            $this->checkUserHasRightsToFile($user, $file);

            $this->files->remove($user, $file);

            $fullFilename = __DIR__ . self::UPLOAD_DIRECTORY . $filename;
            if (file_exists($fullFilename)) 
            {
                unlink($fullFilename);
            }
        }
        catch (Exception $ex)
        {
            throw new FileServiceException($ex->getMessage());
        }
    }

    public function checkUserHasRightsToFile(User $user, File $file)
    {
        if ($user->getId() != $file->getUser()->getId()) 
        {
            throw new FileServiceException('the user can not get access to file with that filename!');
        }
    }

    public function updateFile($username, $filename, $fileContent)
    {
        try
        {
            $user = $this->users->getByUsername($username);
            $file = $this->files->getByFilename($filename);

            $this->checkUserHasRightsToFile($user, $file);

            $this->saveFileContent($fileContent);
        }
        catch (Exception $ex)
        {
            throw new FileServiceException($ex->getMessage());
        }
    }

    public function getFileMetadata($filename)
    {
        try
        {
            $path = __DIR__ . self::UPLOAD_DIRECTORY . $filename;

            if (!file_exists($path)) 
            {
                throw new FileServiceException('file with this filename does not exist!');
            }

            $metadata = [
                'filename' => $filename,
                'type' => filetype($path),
                'mime_type' => mime_content_type($path),
                'size' => filesize($path),
                'modified' => date ("F d Y H:i:s.", filemtime($path))
            ];

            return $metadata;
        }
        catch (Exception $ex)
        {
            throw new FileServiceException($ex->getMessage());
        }
    }
}