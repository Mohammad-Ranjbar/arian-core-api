<?php

namespace App\Services;

use Icewind\SMB\Exception\DependencyException;
use Icewind\SMB\Exception\InvalidTypeException;
use Icewind\SMB\Exception\NotFoundException;
use Icewind\SMB\IShare;
use Icewind\SMB\ServerFactory;
use Icewind\SMB\BasicAuth;

class SMBService
{
    private IShare $share;

    /**
     * @throws DependencyException
     */
    public function __construct($host, $username, $password, $workgroup, $share)
    {
        $serverFactory = new ServerFactory();
        $auth = new BasicAuth($username, $workgroup, $password);
        $server = $serverFactory->createServer($host, $auth);

        $this->share = $server->getShare($share);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidTypeException
     */
    public function getFiles($path): array
    {
        return $this->share->dir($path);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidTypeException
     */
    public function getFile($path)
    {
        return $this->share->read($path);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidTypeException
     */
    public function remove($path): bool
    {
        return $this->share->del($path);
    }
}