<?php

namespace Nazmulpcc\Imap;

use Evenement\EventEmitter;
use Nazmulpcc\Imap\Types\ImapString;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Stream\WritableStreamInterface;

class ImapConnection extends EventEmitter implements ConnectionInterface
{
    private ?ConnectionInterface $connection;

    private bool $closed = false;

    private string $commandPrefix;

    private int $sequence = 0;

    private string $buffer = '';

    private int $bufferLength = 0;

    public function __construct(?ConnectionInterface $connection = null)
    {
        $this->registerConnection($connection);
        $this->generateCommandPrefix();
    }

    public function connect(string $host, int $port = 993): PromiseInterface
    {
        $uri = "tls://{$host}:{$port}";
        $connector = new Connector();

        return $connector->connect($uri)
            ->then(function (ConnectionInterface $connection){
                $this->registerConnection($connection);
                return $connection;
            });
    }

    public function getCommandPrefix(): ?string
    {
        return $this->commandPrefix ?? null;
    }

    public function write($data): bool
    {
        $data = trim($data);
        $command = "{$this->commandPrefix}{$this->sequence} {$data}\r\n";

        if ($this->connection->write($command)){
            ++$this->sequence;
            return true;
        }
        return false;
    }

    public function handleData($data)
    {
        /**
         * Server sent data might start with +, * or a command tag
         * https://datatracker.ietf.org/doc/html/rfc3501#section-2.2.2
         */
        if ($data === '+'){
            $this->emit('continue');
            return;
        }
        echo str_repeat('-', 100) . "\n";
        echo $data;
        echo str_repeat('-', 100) . "\n";
        $tokens = (new ImapString(trim($data)))->tokens();
        $this->emit('data', [$tokens]);
    }

    public function getRemoteAddress(): ?string
    {
        return $this->connection->getRemoteAddress();
    }

    public function getLocalAddress(): ?string
    {
        return $this->connection->getLocalAddress();
    }

    public function isReadable(): bool
    {
        return $this->connection->isReadable();
    }

    public function pause()
    {
        $this->connection->pause();
    }

    public function resume()
    {
        $this->connection->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        // TODO: Implement pipe() method.
    }

    public function close()
    {
        if($this->closed){
            return;
        }
        $this->closed = true;
        $this->connection->close();
        $this->emit('close');
    }

    public function isWritable(): bool
    {
        return $this->connection->isWritable();
    }

    public function end($data = null)
    {
        $this->write($data);

        $this->close();
    }

    protected function registerConnection(?ConnectionInterface $connection)
    {
        if(!$connection){
            return;
        }

        $connection->on('data', [$this, 'handleData']);
        $connection->on('close', [$this, 'close']);

        $this->connection = $connection;
        $this->emit('connected', [$this]);
    }

    protected function generateCommandPrefix()
    {
        $this->commandPrefix = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
    }
}