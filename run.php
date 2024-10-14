<?php
error_reporting(E_ALL);
require 'src/Inotify.php';
require 'src/Worker.php';
require 'src/DB.php';

use Src\Configuration\Inotify\Inotify;
use Src\Configuration\Worker;

require 'vendor/autoload.php';


if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la línea de comandos.\n");
}

echo "Ejecutando el script desde la línea de comandos...\n";


$worker = new Worker\Worker();
$worker->initJobs();


