<?php

use Nazmulpcc\Imap\Imap;
use React\EventLoop\Loop;

use function React\Async\await;

require __DIR__ . '/../vendor/autoload.php';

$imap = new Imap('imap.gmail.com');

await($imap->connect());

Loop::addPeriodicTimer(5, function () use($imap) {
    $imap->noop()->then(function ($data) {
        //
    });
});