<?php

namespace Nazmulpcc\Imap\Types;

use Nazmulpcc\Imap\Imap;

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

        if($sflag = array_intersect($list, $sflags)){
            $this->sflag = $sflag[0];
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
}