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
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename.'/log_' . date('d-m-Y') . '.log';
        file_put_contents($log_file_data, date('Y-m-d h:s').' : '. $log_msg . "\n", FILE_APPEND);
    }

}