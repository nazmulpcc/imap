<?php

namespace Nazmulpcc\Imap;

use Nazmulpcc\Imap\Types\Mail;
use Nazmulpcc\Imap\Types\Mailbox;

class Parser 
{
    public function __construct(protected Imap $imap, protected $commandPrefix)
    {
    }

    public function parseMailboxList(Response $response)
    {
        if(!$response->isOkay()){
            return false;
        }
        $boxes = [];
        $tokens = $response->body()->group()->tokens();

        foreach ($tokens as $line) {
            $box = Mailbox::make($this->imap)
                ->flags($line[1]);
            unset($line[0], $line[1]);
            $box->path(implode('', $line));
            $boxes[] = $box;
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
        $tokens = $response->body()
            ->group()->tokens();
        $count = count($tokens);

        for ($i=0; $i<$count; $i++) {
            $item = $tokens[$i];
            if(strtolower($item[0]) === 'ok'){
                $key = $item[1][0];
                $value = $item[1][1];
            }else{
                $key = $item[1];
                $value = $item[0];
                if(is_array($key)){
                    list($key, $value) = [$value, $key];
                }
            }

            $data[strtolower($key)] = $value;
        }

        return $data;
    }

    public function parseFetchResponse(Response $response): array
    {
        $mails = [];
        $tokens = $response->body()->group()->tokens();

        foreach ($tokens as $mail){
            $mails[] = (new Mail($this->imap))->setResponseTokens($mail);
        }

        return $mails;
    }
}
