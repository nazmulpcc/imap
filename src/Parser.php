<?php

namespace Nazmulpcc\Imap;

use Nazmulpcc\Imap\Exceptions\InvalidResponseException;
use Nazmulpcc\Imap\Types\Astring;
use Nazmulpcc\Imap\Types\Atom;
use Nazmulpcc\Imap\Types\Mailbox;

class Parser 
{
    public function __construct(protected Imap $imap, protected $commandPrefix)
    {
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

    public function parseMailboxList(Response $response)
    {
        if(!$response->isOkay()){
            return false;
        }
        $boxes = [];
        foreach ($response->lines() as $line) {
            $line = new Astring($line);
            $line->expect('* LIST')
                ->skipSpaces()
                ->ifNext('(', function (Astring $line) use(&$flags){
                    $flags = explode(' ', $line->readUntil(')'));
                    $flags = array_map([Atom::class, 'make'], $flags);
                });
            $boxPath = Atom::make($line->skipSpaces()->readUntilEnd())->cleaned();
            $boxes[] = Mailbox::make($this->imap)
                ->flags($flags)
                ->path($boxPath);
        }
        return $boxes;
    }

    public function parseMailboxStatus(Response $response)
    {
        $data = [
            'flags' => [],
            'exists' => 0,
            'recent' => 0,
            'unseen' => 0,
            'uidvalidity' => 0,
            'uidnext' => 0,
        ];
        /** @var Astring[] $lines */
        $lines = array_map([Astring::class, 'make'], $response->lines());

        foreach ($lines as $line) {
            $key = $value = null;
            $line->expect('*')->skipSpaces();
            if($line->isNext('OK')) {
                $key = $line->expect('[')
                    ->readUntil(' ');
                if($line->isNext('(')) {
                    $value = explode(' ', $line->readUntil(')'));
                    $value = array_map([Atom::class, 'make'], $value);
                }else{
                    $value = $line->readUntil(']');
                }
            }elseif($line->isNext('FLAGS')){
                $key = 'flags';
                $flags = $line->skipSpaces()
                    ->expect('(')
                    ->readUntil(')');
                $value = array_map([Atom::class, 'make'], explode(' ', $flags));
            }else{
                $value = $line->skipSpaces()->readUntil(' ');
                $value = is_numeric($value) ? intval($value) : $value;
                $key = trim($line->readUntilEnd());
            }
            $data[strtolower($key)] = $value;
        }

        return $data;
    }

    public function parseFetchResponse(Response $response)
    {
        file_put_contents('original.txt', $response);
        $body = new Astring($response->body());
        return $body->parseNextSpecials();
    }
}
