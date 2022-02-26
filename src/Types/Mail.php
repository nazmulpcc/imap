<?php

namespace Nazmulpcc\Imap\Types;

class Mail extends Type implements \JsonSerializable
{
    protected string $uid;

    protected array $attributes;

    public function setResponseTokens($tokens): static
    {
        $this->setUid($tokens[0]);
        for ($i=0; $i<count($tokens[2]); $i+=2){
            $this->attributes[$tokens[2][$i]] = $tokens[2][$i+1] ?? null;
        }

        return $this;
    }

    protected function setUid(string $uid)
    {
        $this->uid = $uid;
    }

    public function attributes($key = false): mixed
    {
        if(!$key){
            return $this->attributes;
        }
        return $this->attributes[$key] ?? null;
    }

    public function jsonSerialize(): string
    {
        return json_encode($this->attributes);
    }
}