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
        catch (FileException $ex)
        {
            throw new FileRepositoryException($ex);
        }
    }

    public function getFile($filename)
    {
        $fullFilename = __DIR__ . self::UPLOAD_DIRECTORY . $filename;

        if (!file_exists($fullFilename))
        {
            throw new FileServiceException('file not found on disk!');
        }

        return $fullFilename;
    }

    public function createFile($login, $filename, $fileContent)
    {
        try
        {
            $user = $this->users->getUserByLogin($login);
            $file = new File($filename, $user);

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
            $filename = $fileContent->getClientOriginalName();
            $fileContent->move(__DIR__ . self::UPLOAD_DIRECTORY, $filename);
        }
        catch (FileException $ex)
        {
            throw new FileRepositoryException($ex);
        }
    }

    public function deleteFile($login, $filename)
    {
        try
        {
            $user = $this->users->getUserByLogin($login);
            $file = $this->files->getByFilename($filename);

            $this->checkUserHasRightsToFile($user, $file);

            $this->files->remove($user, $file);

            $fullFilename = __DIR__ . self::UPLOAD_DIRECTORY . $filename;
            $this->removeExistingFileFromDisk($fullFilename);
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
            throw new FileServiceException('wtf? file is not yours!');
        }
    }

    public function removeExistingFileFromDisk($fullFilename)
    {
        if (file_exists($fullFilename)) 
        {
            unlink($fullFilename);
        }
    }

    public function updateFile($login, $filename, $fileContent)
    {
        try
        {
            $user = $this->users->getUserByLogin($login);
            $file = $this->files->getByFilename($filename);

            $this->checkUserHasRightsToFile($user, $file);

            $this->files->save($file);
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
                throw new FileServiceException('no such file on disk! go straight ahead the forest');
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