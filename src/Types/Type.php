<?php

namespace Nazmulpcc\Imap\Types;

use Nazmulpcc\Imap\Imap;

abstract class Type
{
    use Makable;

    public function __construct(protected Imap $imap)
    {
    }
}