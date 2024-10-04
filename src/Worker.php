<?php
namespace Src\Configuration\Worker;
use Src\Configuration\DB\DB;
use Src\Logs\Logs\Logs;

use Symfony\Component\Yaml\Yaml;

class Worker
{
    private $config;
    private $logs;
    private $db;

    public function __construct(){
        $this->config = Yaml::parseFile('./config.yaml');
        $this->logs = new Logs();
        $this->db = new DB();
    }


    public function popJobs(){
        return $this->db->query('SELECT * FROM jobs WHERE status="queued" LIMIT 1')->fetch_assoc();
    }


    public function initJobs(){
        while (true) {
          $this->processJobs($this->popJobs());
        }
    }

    private function processJobs($jobs){
        switch ($jobs['type']) {
            case 'xls';
                if (file_put_contents('store/'.basename($jobs['payload']), file_get_contents($jobs['payload'])))
                {
                    echo "File downloaded successfully";
                }
                else
                {
                    echo "File downloading failed.";
                }
        }
    }



}