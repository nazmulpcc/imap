<?php

namespace Nazmulpcc\Imap;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\Connection as Socket;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Stream\WritableResourceStream;

class Connection {
    use Debuggable;
    /**
     * @var ConnectionInterface
     */
    protected ConnectionInterface $connection;

    protected int $sequence = 0;

    protected int $outputSequence = 0;

    protected string $commandPrefix;

    /**
     * @var Deferred[]
     */
    protected array $promises;

    /**
     * @var string[]
     */
    protected array $buffers;

    public function __construct(protected string $host, protected int $port = 993)
    {
        $this->generateCommandPrefix();
    }

    public function connect()
    {
        $connector = new Connector();
        $uri = "tls://{$this->host}:{$this->port}";
        
        $deferred = new Deferred();

        $connector->connect($uri)->then(function(Socket $connection) use($deferred){
            $this->connection = $connection;
            $this->connection->on('data', [$this, 'handleData']);
            $this->write('NOOP'); // To clean up the first incoming data after connection
            $deferred->resolve($this->connection);
        });

        return $deferred->promise();
    }

    public function write(string $command): PromiseInterface
    {
        $deferred = $this->promises[] = new Deferred();
        $sequence = $this->sequence++;
        $this->buffers[$sequence] = '';
        $command = "{$this->commandPrefix}{$sequence} {$command}\r\n";

        $this->debug("<- $command");
        
        if(!$this->connection->write($command)){
            $deferred->reject(new \Exception('Failed to write'));
        }
        return $deferred->promise();
    }

    public function handleData($data)
    {
        $prefixLength = strlen($this->commandPrefix);

        foreach(explode("\n", $data) as $target){
            $this->debug("-> $target");
            if(!isset($this->buffers[$this->outputSequence])){
                $this->buffers[$this->outputSequence] = '';
            }
            $this->buffers[$this->outputSequence] .= $target . "\n";

            if(substr($target, 0, $prefixLength) === $this->commandPrefix){
                $this->debugSection($this->outputSequence);
                $this->promises[$this->outputSequence]->resolve([$this->buffers[$this->outputSequence], $this->getCommandPrefix() . $this->outputSequence]);
                ++$this->outputSequence;
            }
        }
    }

    public function generateCommandPrefix()
    {
        return $this->commandPrefix = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
    }

    public function getCommandPrefix(): string
    {
        return $this->commandPrefix ?? '';
    }
}