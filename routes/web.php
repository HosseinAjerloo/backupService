<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;
use \Illuminate\Support\Facades\File;
Route::get('/', function () {

    $remoteIp = '192.168.171.13';
    $remoteUser = 'dbbbackup';
    $remoteDirectory = '/var/backups/mysql';
    $localDirectory = '/backup/merikh';

    File::ensureDirectoryExists($localDirectory);


    $process = new Process([
        'ssh',
        "{$remoteUser}@{$remoteIp}",
        "test -d {$remoteDirectory}"
    ]);
    
    $process->run();

    if (! $process->isSuccessful()) {
        throw new Exception('Remote backup directory does not exist.');
    }

    $process = new Process([
        'ssh',
        "{$remoteUser}@{$remoteIp}",
        "ls -t {$remoteDirectory}/*.sql 2>/dev/null | head -1"
    ]);

    $process->run();

    if (! $process->isSuccessful()) {
        throw new Exception($process->getErrorOutput());
    }

    $lastFile = trim($process->getOutput());

    if ($lastFile === '') {
        throw new Exception('No backup file found.');
    }

    $process = new Process([
        'rsync',
        '-av',
        "{$remoteUser}@{$remoteIp}:{$lastFile}",
        $localDirectory,
    ]);

    $process->run();

    if (! $process->isSuccessful()) {
        throw new Exception($process->getErrorOutput());
    }

    echo "Backup downloaded successfully.";
});
