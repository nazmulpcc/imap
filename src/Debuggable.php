<?php

namespace Nazmulpcc\Imap;

use React\Stream\WritableResourceStream;

trait Debuggable
{
    protected ?WritableResourceStream $debugger = null;

    public function debug($string): bool
    {
        return $this->debugger &&
            $this->debugger->write($string . "\n");
    }

    public function setDebugger(?WritableResourceStream $stream): static
    {
        $stream &&
            ($this->debugger = $stream);

        return $this;
    }

    public function debugSection($title = false, $separator = '-')
    {
        $cols = $this->columns();
        if(!$title){
            $this->debug(str_repeat($separator, $cols));
        }
        $cols -= strlen($title) + 2;
        $cols = floor($cols/2);
        $this->debug(str_repeat('-', $cols) . " {$title} " . str_repeat('-', $cols));
    }

    protected function columns()
    {
        try {
            return (int) exec('tput cols');
        }catch (\Exception $e){
            return 100;
        }
    }
}