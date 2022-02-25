<?php

namespace Nazmulpcc\Imap;

use Nazmulpcc\Imap\Exceptions\NotLoggedInException;
use Nazmulpcc\Imap\Types\Mailbox;
use React\Promise\PromiseInterface;
use React\Stream\WritableResourceStream;

class Imap {
    use Debuggable;

    protected Connection $connection;

    protected Parser $parser;

    protected bool $loggedIn = false;

    protected array $capabilities;

    protected string $status = 'idle';

    public function __construct(protected string $host, protected int $port = 993, bool $debug = true)
    {
        $this->connection = new Connection($this->host, $this->port);
        $this->parser = new Parser($this, $this->connection->getCommandPrefix());

        if($debug){
            $this->debugWith(new WritableResourceStream(STDOUT));
        }
    }

    public function debugWith(WritableResourceStream $stream)
    {
        $this->setDebugger($stream);
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
        return $this->write('NOOP');
    }

    public function login(string $user, string $pass): PromiseInterface
    {
        return $this
            ->write("LOGIN \"{$user}\" \"{$pass}\"")
            ->then(function (Response $response){
                if(!$response->isOkay()){
                    return $this->loggedIn = false;
                }
                $this->capabilities = $this->parser->parseCapabilitiesFromLoginResponse($response->body());
                return $this->loggedIn = count($this->capabilities) > 0;
            });
    }

    public function create(string $box): PromiseInterface
    {
        return $this->write('CREATE ' . $box)
            ->then([$this, 'checkResponseIsOkay']);
    }

    public function delete(string $box): PromiseInterface
    {
        return $this->write('DELETE ' . $box)
            ->then([$this, 'checkResponseIsOkay']);
    }

    public function list($reference = '""', $mailbox = '"*"'): PromiseInterface
    {
        return $this->write("LIST {$reference} {$mailbox}")
            ->then(function (Response $response){
                return $this->parser->parseMailboxList($response);
            });
    }

    public function select(Mailbox|string $mailbox, $select = true): PromiseInterface
    {
        if($mailbox instanceof Mailbox){
            $mailbox = $mailbox->path;
        }

        $command = $select ? 'SELECT' : 'EXAMINE';

        return $this->write("{$command} {$mailbox}")
            ->then(function (Response $response){
                return $this->parser->parseMailboxStatus($response);
            });
    }

    public function examine(Mailbox|string $mailbox): PromiseInterface
    {
        return $this->select($mailbox, false);
    }

    public function fetch(string $messageId, $pattern = 'RFC822')
    {
        return $this->write("FETCH {$messageId} {$pattern}")
            ->then(function (Response $response){
                return $this->parser->parseMailBody($response);
            });
    }

    public function namespace(): PromiseInterface
    {
        // TODO: parse namespace response
        return $this->write('NAMESPACE');
    }

    public function write(string $command)
    {
        return $this->connection->write($command)
            ->then(function ($data){
                return new Response($data[0], $data[1], $this->debugger);
            });
    }

    protected function mustBeLoggedIn()
    {
        if(!$this->loggedIn){
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            throw new NotLoggedInException('You must be logged in to call ' . ($trace[1]['function'] ?? 'this function'));
        }
    }

    public function checkResponseIsOkay(Response $response): bool
    {
        return $response->isOkay();
    }
}