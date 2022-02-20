<?php

namespace Nazmulpcc\Imap\Types;

class Atom implements \Stringable
{
    use Makable;

    public function __construct(protected string $body)
    {
    }

    public function concat(string $atom)
    {
        return new static($this->cleaned() . $atom);
    }

    public function cleaned()
    {
        return trim($this->body, '"\/*+');
    }


    public function __toString(): string
    {
        return $this->cleaned();
    }
}