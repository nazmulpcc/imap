<?php

namespace Nazmulpcc\Imap;

use React\Promise\PromiseInterface;
use React\Stream\WritableResourceStream;

class Imap {
    protected Connection $connection;

    public function __construct(protected string $host, protected int $port = 993, bool $debug = true)
    {
        $this->connection = new Connection($this->host, $this->port);
        if($debug){
            $this->debugWith(new WritableResourceStream(STDOUT));
        }
    }

    public function debugWith(WritableResourceStream $stream)
    {
        $this->connection->setDebugger($stream);
    }

    public function connect()
    {
        return $this->connection->connect();
    }

    public function noop(): PromiseInterface
    {
        return $this->connection->write('NOOP');
    }

    public function login(string $user, string $pass): PromiseInterface
    {
        // TODO: parse login response
        return $this->connection->write("LOGIN \"{$user}\" \"{$pass}\"");
    }

    public function namespace(): PromiseInterface
    {
        // TODO: parse namespace response
        return $this->connection->write('NAMESPACE');
    }
}