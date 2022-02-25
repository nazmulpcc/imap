<?php

namespace Nazmulpcc\Imap\Types;

use Nazmulpcc\Imap\Imap;

/**
 * @property string $name
 * @property string[] $flags
 * @property string $sflag
 * @property string $path
 */
class Mailbox extends Type
{
    protected ?Mailbox $parent = null;

    /**
     * @var Mailbox[]
     */
    protected array $children = [];

    /**
     * @var string[]
     */
    protected array $flags = [];

    protected string $sflag;

    protected string $name;

    protected string $path;

    public function path($path): static
    {
        $path = explode('/', $this->path = trim($path));
        $this->name = trim(end($path));

        return $this;
    }

    public function flags(array $list = []): array|static
    {
        if(count(func_get_args()) === 0){
            return $this->flags;
        }

        $list = array_map('strtolower', $list); // TODO: implement a way to retain original flags instead of the case insensitive ones?
        $sflags = ['noselect', 'marked', 'unmarked'];

        // Remove sflag from existing flags
        // See mbx-list-sflag from https://datatracker.ietf.org/doc/html/rfc3501#section-9
        $flags = array_diff($list, $sflags);
        $this->flags = $flags; // TODO: maybe validate the flags?

        if(count($sflag = array_intersect($list, $sflags)) > 0){
            $this->sflag = end($sflag);
        }

        return $this;
    }

    public function marked(): bool
    {
        return strtolower($this->sflag) === 'marked';
    }

    public function unmarked(): bool
    {
        return strtolower($this->sflag) === 'unmarked';
    }

    public function noselect(): bool
    {
        return strtolower($this->sflag) === 'noselect';
    }

    public function __get(string $name)
    {
        return $this->$name ?? null;
    }
}