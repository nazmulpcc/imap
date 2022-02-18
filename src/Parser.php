<?php

namespace Nazmulpcc\Imap;

use Nazmulpcc\Imap\Exceptions\InvalidResponseException;

class Parser 
{
    public function __construct(protected string $commandPrefix)
    {
        //
    }

    /**
     * @throws InvalidResponseException
     */
    public function parseCapabilitiesFromLoginResponse(string $output): array
    {
        $this->cleanOutput($output);
        $start = strpos($output, '[');
        $end = strpos($output, ']');
        if($end === false || $start === false){
            throw new InvalidResponseException("Response doesn't contain ]");
        }
        return explode(' ', substr($output, $start + 1, $end - $start - 1));
    }

    public function cleanOutput(string &$output)
    {
        $output = trim($output);
        $output = preg_replace("~{$this->commandPrefix}\d+ ~", '', $output);
    }
}
