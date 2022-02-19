<?php

namespace Nazmulpcc\Imap\Types;

use Nazmulpcc\Imap\Imap;

abstract class Type
{
    public function __construct(protected Imap $imap)
    {
    }
}