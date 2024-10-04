<?php
    error_reporting(E_ALL);
    require 'src/Inotify.php';
    require 'src/Worker.php';
    require 'src/DB.php';

    use Src\Configuration\Inotify\Inotify;
    use Src\Configuration\Worker;

    require 'vendor/autoload.php';


    $worker = new Worker\Worker();
    $worker->initJobs();


