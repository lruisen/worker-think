<?php

namespace WorkerThink;

use think\App;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * @property Request $woRequest
 * @property TcpConnection $connection
 */
class WorkerHttpApp extends App
{
    private Request $woRequest;
    private TcpConnection $connection;

    public function init(TcpConnection $connection, Request $request, array $server = []): void
    {
        $this->woRequest = $request;
        $this->connection = $connection;
        $this->setRuntimePath(root_path() . 'runtime' . DIRECTORY_SEPARATOR);

        $scriptFilePath = public_path() . 'index.php';
        $_SERVER = array_merge($server, [
            'QUERY_STRING' => $request->queryString(),
            'REQUEST_TIME' => time(),
            'REQUEST_METHOD' => $request->method(),
            'REQUEST_URI' => $request->uri(),
            'SERVER_NAME' => $request->host(true),
            'SERVER_PROTOCOL' => 'HTTP/' . $request->protocolVersion(),
            'SERVER_ADDR' => $connection->getLocalIp(),
            'SERVER_PORT' => $connection->getLocalPort(),
            'REMOTE_ADDR' => $connection->getRemoteIp(),
            'REMOTE_PORT' => $connection->getRemotePort(),
            'SCRIPT_FILENAME' => $scriptFilePath,
            'SCRIPT_NAME' => DIRECTORY_SEPARATOR . pathinfo($scriptFilePath, PATHINFO_BASENAME),
            'DOCUMENT_ROOT' => dirname($scriptFilePath),
            'PATH_INFO' => $request->path(),
        ]);

        $headers = $request->header();
        foreach ($headers as $key => $item) {
            $hKey = str_replace('-', '_', $key);
            if ($hKey == 'content_type') {
                $_SERVER['CONTENT_TYPE'] = $item;
                continue;
            }

            if ($hKey == 'content_length') {
                $_SERVER['CONTENT_LENGTH'] = $item;
                continue;
            }

            $hKey = strtoupper(str_starts_with($hKey, 'HTTP_') ? $hKey : 'HTTP_' . $hKey);
            $_SERVER[$hKey] = $item;
        }

        $_GET = $request->get();
        $_POST = $request->post();
        $_FILES = $request->file();
        $_REQUEST = array_merge($_REQUEST, $_GET, $_POST);

        $this->initialize();
    }
}