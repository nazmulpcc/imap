<?php

use Nazmulpcc\Imap\Imap;

use function React\Async\await;

require __DIR__ . '/../vendor/autoload.php';

$config = require 'config.php';

$imap = new Imap($config['host'], $config['port'], false);

await($imap->connect());

$imap->login($config['user'], $config['pass'])
    ->then(function($data){
        echo "================= login =================\n";
        echo $data;
        echo "================= login =======================\n";
    });

$imap->delete('mailbox-123')
    ->then(function ($data) {
        echo "================= delete =================\n";
        echo var_dump($data);
        echo "================= delete =======================\n";
    });