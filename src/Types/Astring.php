<?php

namespace Nazmulpcc\Imap\Types;

use React\EventLoop\Loop;

class Astring
{
    use Makable;

    protected int $position = 0;

    protected int $length = 0;

    public function __construct(protected string $body)
    {
        $this->length = strlen($this->body);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function next($length = 1): string
    {
        $result = substr($this->body, $this->position, $length);
        $this->position += $length;
        if($this->position > $this->length){
            $this->position = $this->length;
        }

        return $result;
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

    public function readNextWord()
    {
        return $this->readUntil(' ');
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
    public function skipSpaces(): static
    {
        $spaces = [' ', "\r", "\n"];
        while (true) {
            $char = $this->body[$this->position];
            if(in_array($char, $spaces)){
                ++$this->position;
            }else{
                break;
            }
        }

        return $this;
    }

    public function parseNextSpecials()
    {
        $this->position = 0; // start from the beginning
        $items = [];
        $index = 0;

        do {
            $buffer = '';
            $items[$index] = [];
            $this->expect('*');
            $this->skipSpaces();

            while (true) {
                $char = $this->next();
                if($this->ended()){
                    break;
                }
                if ($char === '(') {
                    $items[$index][] = $this->parseList();
                } elseif ($char === '{') {
                    $items[$index][] = $this->parseLiteralString();
                    $this->skipSpaces();
                }elseif ($char === '"'){
                    $items[$index][] = $this->parseQuotedString();
                }elseif ($char === '\\'){
                    $buffer .= $char . $this->next();
                }elseif ($char === ' ') {
                    $items[$index][] = $buffer;
                    $buffer = '';
                }elseif ($char === '*' && $this->body[$this->position-1] !== '\\'){
                    $items[$index][] = $buffer;
                    --$this->position;
                    ++$index;
                    break;
                }else{
                    $buffer .= $char;
                }
            }
        }
        while (!$this->ended());

        return $items;
    }

    protected function parseQuotedString(): string
    {
        $buffer = '';
        while (!$this->ended()){
            $char = $this->next();
            if($char === '\\'){
                $char = $this->next();
            }elseif ($char === '"'){
                return $buffer;
            }
            $buffer .= $char;
        }
        return $buffer;
    }

    protected function parseLiteralString()
    {
        $size = (int) $this->readUntil('}');
        $this->next(2); // skip CRLF after }
        return $this->next($size);
    }

    protected function parseList(): array
    {
        $items = [];
        $buffer = '';

        while (!$this->ended()){
            $char = $this->next();
            if($char === '\\') {
                $buffer .= $this->next(); // TODO: should we keep the "\" ?
            }elseif ($char === '(') {
                $items[] = $this->parseList();
            }elseif ($char === '{'){
                $items[] = $this->parseLiteralString();
                $this->skipSpaces();
            }elseif ($char === '"'){
                $buffer = $this->parseQuotedString();
            }elseif ($char === ' '){
                $items[] = $buffer;
                $buffer = '';
            }elseif ($char === ')'){
                if($buffer !== ''){
                    $items[] = $buffer;
                }
                $this->skipSpaces();
                return $items;
            }else{
                $buffer .= $char;
            }
        }
        return $items;
    }

    protected function ended()
    {
        return $this->position >= $this->length;
    }
}