<?php

namespace DiskApp\Repository;

use Exception;

use DiskApp\Model\User;
use DiskApp\Model\File;

class FileRepositoryException extends Exception { }

class FileRepository extends BaseRepository
{
    public function getByFilename($filename)
    {
        try
        {
            $statement = $this->dbConnection->executeQuery(
                'SELECT f.id, f.filename, f.user_id, u.username, u.hash
                 FROM files f
                 INNER JOIN users u
                 ON f.user_id = u.id
                 WHERE f.filename = ?', 
                [
                    $filename
                ]
            );
            $result = $statement->fetch();

            if ($result == false)
            {
                throw new FileRepositoryException('the user can not get access to file with that filename!');
            }

            $user = new User($result["user_id"], $result["username"], $result["hash"]);
            $file = new File($result["id"], $result["filename"], $user);

            return $file;
        }
        catch(Exception $ex)
        {
            throw new FileRepositoryException($ex->getMessage());
        }
    }

    public function add(File $file)
    {
        try
        {
            $statement = $this->dbConnection->executeQuery(
                'SELECT username FROM users WHERE username = ?',
                [
                    $file->getUser()->getUsername()
                ]
            );
            $specifiedUser = $statement->fetch();

            $statement = $this->dbConnection->executeQuery(
                'SELECT filename FROM files WHERE filename = ?',
                [
                    $file->getFilename()
                ]
            );
            $fileWithThisFilename = $statement->fetch();

            if ($specifiedUser == false || $fileWithThisFilename != false)
            {
                throw new FileRepositoryException('can not add this file from the specified user!');
            }

            $this->dbConnection->executeQuery(
                'INSERT INTO files (filename, user_id) VALUES (?, ?)', 
                [
                    $file->getFilename(), 
                    $file->getUser()->getId()
                ]
            );
        }
        catch(Exception $ex)
        {
            throw new FileRepositoryException($ex->getMessage());
        }
    }

    public function remove(User $user, File $file)
    {
        try
        {
            $deletedRows = $this->dbConnection->executeUpdate(
                'DELETE FROM files WHERE filename = ? AND user_id = ?', 
                [
                    $file->getFilename(),
                    $user->getId()
                ]
            );

            if($deletedRows == 0)
            {
                throw new FileRepositoryException('there is no such file that can be deleted by this user!');
            }
        }
        catch(Exception $ex)
        {
            throw new FileRepositoryException($ex->getMessage());
        }
    }

    public function getFilesList()
    {
        try
        {
            $statement = $this->dbConnection->executeQuery(
                'SELECT f.filename, u.username
                 FROM files f
                 INNER JOIN users u
                 ON f.user_id = u.id');
            $result = $statement->fetchAll();

            if ($result == false)
            {
                throw new FileRepositoryException('file repository is empty!');
            }

            return $result;
        }
        catch(Exception $ex)
        {
            throw new FileRepositoryException($ex->getMessage());
        }
    }
}