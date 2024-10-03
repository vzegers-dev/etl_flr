<?php

namespace Src\Logs\Logs;
use Symfony\Component\Yaml\Yaml;
class Logs
{
    private $path;
    public function __construct(){
        $this->path = Yaml::parseFile('./config.yaml');
    }
    function message($log_msg)
    {
        $log_filename = $this->path['logs']['path'];
        if (!file_exists($log_filename))
        {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename.'/log_' . date('d-m-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }

}