<?php

namespace Nazmulpcc\Imap;

class Response implements \ArrayAccess, \Stringable
{
    use Debuggable;

    protected string $body;

    protected array $lines = [];

    /**
     * Imap response status, OK, NO or BAD
     * @var string
     */
    protected string $status;

    public function __construct(string $body, protected string $prefix, $debugStream = null)
    {
        $this->body = trim($body);

        $this->setDebugger($debugStream)
            ->processResponseBody();
    }

    public function body(): string
    {
        return $this->body;
    }

    public function lines(): array
    {
        return $this->lines;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isOkay(): bool
    {
        return $this->status === 'OK';
    }

    public function isNo(): bool
    {
        return $this->status === 'NO';
    }

    public function isBad(): bool
    {
        return $this->status === 'BAD';
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->lines[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->lines[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->lines[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->lines[$offset]);
    }

    public function __toString(): string
    {
        return $this->body;
    }

    protected function processResponseBody()
    {
        $response = $this->body;
        $this->debug('=> Processing response of length ' . strlen($response));
        while(strlen($response) > 0) {
            $end = strpos($response, "\n");
            $line = substr($response, 0, $end ?: null);
            $response = substr($response, $end+1);

            if(str_starts_with($line, $this->prefix)){
                $this->processLastLine($line);
                break;
            }
            // TODO: need additional processing
            $this->lines[] = $line;
        }
    }

    protected function processLastLine(string $line)
    {
        // Remove the command prefix from the last line of output
        $line = str_replace($this->prefix . ' ', '', trim($line));

        // Next word should be OK, NO or BAD
        $this->status = substr($line, 0, strpos($line, ' '));
    }
}