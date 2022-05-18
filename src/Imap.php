<?php

namespace Nazmulpcc\Imap;

use Nazmulpcc\Imap\Exceptions\NotLoggedInException;
use Nazmulpcc\Imap\Types\Mailbox;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;

class Imap {
    use Debuggable;

    protected Parser $parser;

    protected bool $loggedIn = false;

    protected array $capabilities;

    protected string $status = 'idle';

    public function __construct(protected ?ImapConnection $connection = null)
    {
        if(!$this->connection){
            $this->connection = new ImapConnection();
        }
        $this->parser = new Parser($this, $this->connection->getCommandPrefix());
    }

    public function connection(): ImapConnection
    {
        return $this->connection;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function capabilities(): array
    {
        return $this->capabilities;
    }

    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    public function connect(string $host, int $port = 993): PromiseInterface
    {
        $this->status = 'connecting';
        return $this->connection
            ->connect($host, $port)
            ->then(function (ConnectionInterface $connection){
                $connection->once('data', function ($data){
                    return $this->status = 'connected';
                });
            });
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
                $this->capabilities = [];
                return $this->loggedIn = count($this->capabilities) > -1;
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
                return $this->parser->parseFetchResponse($response);
            });
    }

    public function namespace(): PromiseInterface
    {
        // TODO: parse namespace response
        return $this->write('NAMESPACE');
    }

    public function write(string $command)
    {
        $this->connection->write($command);
//            ->then(function ($data){
//                return new Response($data[0], $data[1], $data[2]);
//            });

        return new Promise(fn() => null);
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