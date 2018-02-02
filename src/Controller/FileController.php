<?php

namespace DiskApp\Controller;

use Exception;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use DiskApp\Service\UserService;
use DiskApp\Service\FileService;

class FileController extends BaseController
{
    protected $userService;
    private $fileService;

    public function __construct(UserService $userService, FileService $fileService)
    {
        parent::__construct($userService);
        $this->fileService = $fileService;
    }

    public function getFilesList(Request $request)
    {
        try
        {
            $username = $request->getUser();
            $password = $request->getPassword();
            $this->checkAuthenticationData($username, $password);

            $files = $this->fileService->getFilesList();

            return new JsonResponse($files, Response::HTTP_OK);
        }
        catch (Exception $ex)
        {
            return new JsonResponse(['message' => $ex->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function getFile(Request $request, $filename)
    {
        try
        {
            $username = $request->getUser();
            $password = $request->getPassword();
            $this->checkAuthenticationData($username, $password);

            $fullFilename = $this->fileService->getFile($filename);

            $response = new BinaryFileResponse($fullFilename);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
            
            return $response;
        }
        catch (Exception $ex)
        {
            return new JsonResponse(['message' => $ex->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function putFile(Request $request)
    {
        try
        {
            $username = $request->getUser();
            $password = $request->getPassword();
            $this->checkAuthenticationData($username, $password);

            $fileContent = $request->files->get('file');
            if (!isset($fileContent))
            {
                return new JsonResponse(['message' => 'no files found in this request!'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $filename = $fileContent->getClientOriginalName();
            
            $this->fileService->createFile($username, $filename, $fileContent);

            return new JsonResponse(['message' => 'file was successfully put!'], Response::HTTP_CREATED);
        }
        catch (Exception $ex)
        {
            return new JsonResponse(['message' => $ex->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function deleteFile(Request $request, $filename)
    {
        try
        {
            $username = $request->getUser();
            $password = $request->getPassword();
            $this->checkAuthenticationData($username, $password);

            $this->fileService->deleteFile($username, $filename);

            return new JsonResponse(['message' => 'file was successfully deleted!'], Response::HTTP_CREATED);
        }
        catch (Exception $ex)
        {
            return new JsonResponse(['message' => $ex->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function updateFile(Request $request)
    {
        try
        {
            $username = $request->getUser();
            $password = $request->getPassword();
            $this->checkAuthenticationData($username, $password);

            $fileContent = $request->files->get('file');

            if (!isset($fileContent))
            {
                return new JsonResponse(['message' => 'no files found in this request!'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $filename = $fileContent->getClientOriginalName();
            
            $this->fileService->updateFile($username, $filename, $fileContent);

            return new JsonResponse(['message' => 'file was successfully updated!'], Response::HTTP_CREATED);
        }
        catch (Exception $ex)
        {
            return new JsonResponse(['message' => $ex->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function getFileMetadata(Request $request, $filename)
    {
        try
        {
            $username = $request->getUser();
            $password = $request->getPassword();
            $this->checkAuthenticationData($username, $password);
            
            $metadata = $this->fileService->getFileMetadata($filename);

            return new JsonResponse($metadata, Response::HTTP_CREATED);
        }
        catch (Exception $ex)
        {
            return new JsonResponse(['message' => $ex->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

}