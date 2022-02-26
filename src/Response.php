<?php

namespace Nazmulpcc\Imap;

use Nazmulpcc\Imap\Types\ImapString;

class Response implements \Stringable
{
    use Debuggable;

    protected array $tokens = [];

    /**
     * Imap response status, OK, NO or BAD
     * @var string
     */
    protected string $status;

    protected ?string $specialStatus = null;

    protected ?string $statusMessage = null;

    protected ImapString $body;

    protected ImapString $statusLine;

    public function __construct(string $body, string $statusLine, protected string $prefix)
    {
        $this->body = new ImapString($body);
        $this->statusLine = new ImapString($statusLine);
        $this->status = $this->statusLine->tokens()[1] ?? 'BAD';
    }

    public function body(): ImapString
    {
        return $this->body;
    }

    public function statusLine(): ImapString
    {
        return $this->statusLine;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function specialStatus(): ?string
    {
        return $this->specialStatus;
    }

    public function statusMessage(): ?string
    {
        return $this->statusMessage;
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

    public function __toString(): string
    {
        return $this->body->body();
    }
}