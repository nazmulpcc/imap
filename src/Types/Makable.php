<?php

namespace Nazmulpcc\Imap\Types;

trait Makable
{
    public static function make(): static
    {
        $args = func_get_args();

        return new static(...$args);
    }
}