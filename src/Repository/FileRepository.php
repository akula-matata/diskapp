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
                'SELECT f.id, f.filename, f.user_id, u.id, u.username, u.hash
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
                throw new FileRepositoryException('no one file row for you!');
            }

            $user = new User($result["username"], $result["hash"]);
            $user->setId($result["user_id"]);

            $file = new File($result["filename"], $user);
            $file->setId($result["id"]);

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
            $insertedRows = $this->dbConnection->executeQuery(
                'INSERT INTO files (filename, user_id) VALUES (?, ?)', 
                [
                    $file->getFilename(), 
                    $file->getUser()->getId()
                ]
            );

            if($insertedRows == 0)
            {
                throw new FileRepositoryException('the file row can not be inserted into table!');
            }
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
                throw new FileRepositoryException('the file row can not be deleted from table!');
            }
        }
        catch(Exception $ex)
        {
            throw new FileRepositoryException($ex->getMessage());
        }
    }

    public function save(File $file)
    {
        
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
                throw new FileRepositoryException('no one file row for you!');
            }

            return $result;
        }
        catch(Exception $ex)
        {
            throw new FileRepositoryException($ex->getMessage());
        }
    }
}