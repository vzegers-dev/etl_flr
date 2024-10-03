<?php
    require 'src/Inotify.php';

    use Src\Configuration\Inotify\Inotify;

    require 'vendor/autoload.php';


    $inotify = new Inotify();
    $inotify->start();


