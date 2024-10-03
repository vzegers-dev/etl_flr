<?php
namespace Src\Configuration\Database;
use Src\Logs\Logs\Logs;
use Symfony\Component\Yaml\Yaml;

class Database
{
    private $config;
    private $logs;
    public function __construct(){
        $this->config = Yaml::parseFile('./config.yaml');
        $this->logs = new Logs();
    }

    public function connection(){
        while (true) {
            $this->logs->message(getmypid());
        }
    }

}