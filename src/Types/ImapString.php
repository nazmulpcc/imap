<?php

namespace Nazmulpcc\Imap\Types;

class ImapString
{
    CONST SPACES = [" ", "\n", "\r"];

    protected string $body;

    protected int $length;

    protected array $tokens = [];

    public function __construct(string $body)
    {
        $this->body = trim($body);
        $this->length = strlen($this->body);
        $this->parse($this->tokens);
    }

    public function tokens(): array
    {
        return $this->tokens;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function group(): static
    {
        $tokens = $this->tokens();
        $count = count($tokens);
        $index = -1;

        unset($this->tokens); // TODO: free memory, but do we really need this?
        $this->tokens = [];
        for($i=0; $i<$count; $i++){
            if($tokens[$i][0] === '*'){
                ++$index;
            }else{
                $this->tokens[$index][] = $tokens[$i];
                unset($tokens[$i]);
            }
        }

        return $this;
    }

    public function parse(&$tokens, &$position = 0, ?string $listType = null)
    {
        $tokens = [];
        $buffer = '';
        if ($this->body === $buffer) {
            return;
        }
        $this->skipSpaces($position);
        do {
            $char = $this->body[$position];
            ++$position;
            if (in_array($char, static::SPACES)) {
                $this->recordValidBuffer($tokens, $buffer);
                $buffer = '';
            } elseif ($char === '{') {
                $this->recordValidBuffer($tokens, $buffer);
                $tokens[] = $this->parseLiterString($position);
            } elseif ($char === '"') {
                $this->recordValidBuffer($tokens, $buffer);
                $tokens[] = $this->parseQuotedString($position);
            } elseif (($char === '(' || ($char === '[' && strlen($buffer) === 0)) && $this->body[$position - 1] !== '\\') {
                $list = [];
                $this->parse($list, $position, $char);
                $tokens[] = $list;
            } elseif ($char === ')' && $listType === '(') {
                $this->recordValidBuffer($tokens, $buffer);
                return;
            }elseif ($char === ']' && $listType === '['){
                $this->recordValidBuffer($tokens, $buffer);
                return;
            } else {
                $buffer .= $char;
            }
        } while ($position < $this->length);
        $this->recordValidBuffer($tokens, $buffer);
        if ($listType) {
            $this->skipSpaces($position);
        }
    }

    protected function parseQuotedString(int &$position): string
    {
        $buffer = '';
        while ($position < $this->length) {
            $char = $this->body[$position];
            ++$position;
            if ($char === '\\') {
                $char = $this->body[$position];
                ++$position;
            } elseif ($char === '"') {
                return $buffer;
            }
            $buffer .= $char;
        }
        return $buffer;
    }

    protected function parseLiterString(int &$position): string
    {
        $size = substr($this->body, $position, strpos($this->body, '}', $position) - $position);
        $position += strlen($size) + 3; // length, }, CR, LF
        $token = substr($this->body, $position, (int)$size);
        $position += (int)$size;

        return $token;
    }

    protected function recordValidBuffer(&$tokens, &$buffer)
    {
        $buffer = trim($buffer);
        if ($buffer !== '') {
            $tokens[] = $buffer;
        }
    }

    protected function skipSpaces(int &$position): void
    {
        if ($position >= $this->length) return;
        if (!in_array($this->body[$position], static::SPACES)) {
            return;
        } else {
            ++$position;
            $this->skipSpaces($position);
        }
    }
}