<?php

namespace Nazmulpcc\Imap;

use Nazmulpcc\Imap\Exceptions\NotLoggedInException;
use React\Promise\PromiseInterface;
use React\Stream\WritableResourceStream;

class Imap {
    protected Connection $connection;

    protected Parser $parser;

    protected bool $loggedIn = false;

    protected array $capabilities;

    protected string $status = 'idle';

    public function __construct(protected string $host, protected int $port = 993, bool $debug = true)
    {
        $this->connection = new Connection($this->host, $this->port);
        $this->parser = new Parser($this->connection->getCommandPrefix());

        if($debug){
            $this->debugWith(new WritableResourceStream(STDOUT));
        }
    }

    public function debugWith(WritableResourceStream $stream)
    {
        $this->connection->setDebugger($stream);
    }

    public function connect(): PromiseInterface
    {
        $this->status = 'connecting';
        return $this->connection
            ->connect()
            ->then(function (\React\Socket\Connection $connection){
                $connection->once('data', function ($data){
                    return $this->status = 'connected';
                });
            });
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    public function noop(): PromiseInterface
    {
        return $this->connection->write('NOOP');
    }

    public function login(string $user, string $pass): PromiseInterface
    {
        return $this->connection
            ->write("LOGIN \"{$user}\" \"{$pass}\"")
            ->then(function ($response){
                $this->capabilities = $this->parser->parseCapabilitiesFromLoginResponse($response);
                return $this->loggedIn = count($this->capabilities) > 0; // TODO: need to improve login check logic
            });
    }

    public function create(string $box): PromiseInterface
    {
        return $this->connection->write('CREATE ' . $box)
            ->then([$this->parser, 'isOkay']);
    }

    public function delete(string $box): PromiseInterface
    {
        return $this->connection->write('DELETE ' . $box)
            ->then([$this->parser, 'isOkay']);
    }

    public function namespace(): PromiseInterface
    {
        // TODO: parse namespace response
        return $this->connection->write('NAMESPACE');
    }

    public function write(string $command)
    {
        return $this->connection->write($command);
    }

    protected function mustBeLoggedIn()
    {
        if(!$this->loggedIn){
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
            throw new NotLoggedInException('You must be logged in to call ' . ($trace[1]['function'] ?? 'this function'));
        }
    }
}