<?php

namespace Nazmulpcc\Imap\Types;

class Astring
{
    use Makable;

    protected int $position = 0;

    protected int $length = 0;

    public function __construct(protected string $body)
    {
        $this->length = strlen($this->body);
    }

    /**
     * Expect the provided string to be next, throws an exception otherwise
     * @param string $string
     * @param bool $ignoreSpaces
     * @return $this
     * @throws \Exception
     */
    public function expect(string $string, bool $ignoreSpaces = true): static
    {
        if ($ignoreSpaces){
            $this->skipSpaces();
        }

        if($this->isNext($string)){
           return $this;
        }
        throw new \Exception("Unable to find expected string \"$string\"");
    }

    public function readUntil(string $target): string
    {
        $buffer = '';
        while (!$this->isNext($target) && $this->position <= $this->length){
            $buffer .= $this->body[$this->position];
            ++$this->position;
        }
        return $buffer;
    }

    public function readUntilEnd()
    {
        return substr($this->body, $this->position);
    }

    /**
     * Determine whether provided string is next
     * @param string $string
     * @param bool $movePosition
     * @return bool
     */
    public function isNext(string $string, bool $movePosition = true): bool
    {
        if(substr($this->body, $this->position, $length = strlen($string)) === $string) {
            $movePosition && ($this->position += $length);
            return true;
        }
        return false;
    }

    /**
     * Execute a callback when the next string is the provided string
     * @param string $string
     * @param callable $callback
     * @return $this
     */
    public function ifNext(string $string, callable $callback): static
    {
        if($this->isNext($string)){
            call_user_func($callback, $this);
        }
        return $this;
    }

    /**
     * Execute a callback when the next string is not the provided string
     * @param string $string
     * @param callable $callback
     * @return $this
     */
    public function ifNotNext(string $string, callable $callback): static
    {
        if(!$this->isNext($string)){
            call_user_func($callback, $this);
        }
        return $this;
    }

    /**
     * Read until a non-space character is found
     * @return $this
     */
    public function skipSpaces()
    {
        while ($this->body[$this->position] === ' '){
            ++$this->position;
        }
        return $this;
    }
}