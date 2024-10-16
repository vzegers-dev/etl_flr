<?php

namespace Src\Configuration\Worker;

use Src\Configuration\DB\DB;
use Src\Logs\Logs\Logs;

use Symfony\Component\Yaml\Yaml;
use Box\Spout\Src\Spout\Reader\ReaderFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;
class Worker
{
    private $config;
    private $logs;
    private $db;
    private $rut;

    public function __construct()
    {
        $this->config = Yaml::parseFile('./config.yaml');
        $this->logs = new Logs();
        $this->db = new DB();
        $this->rut = "";
    }


    public function pushJobs()
    {
        return $this->db->query('SELECT * FROM jobs j JOIN campanias c ON j.campania_id = c.id_campania
         WHERE j.status="queued" LIMIT 1')->fetch_assoc();
    }

    public function popJobs($id,$status,$start,$end)
    {
        return $this->db->query("UPDATE jobs SET status='".$status."', 
        start_time='".$start."', end_time='".$end."' WHERE id=" . $id);
    }


    public function initJobs()
    {
        while (true) {
            $this->logs->message('Searching Jobs');
            $job = $this->pushJobs();
            if (count($job) > 0) {
                $this->logs->message('Job Running ID : ' . $job['id']);
                $this->processJobs($job);
            } else {
                $this->logs->message('There is not Jobs, waiting Jobs');
                sleep(10);
            }
        }
    }

    private function processJobs($jobs)
    {
        try {
            switch ($jobs['type']) {
                case 'xls';
                    if (file_put_contents('tmp/' . basename($jobs['payload']),
                        file_get_contents($this->config['url'].'/'.$jobs['payload']))) {
                        $this->logs->message('Download file : ' . basename($jobs['payload']));
                        $this->readXlsxAndInsert('tmp/' . basename($jobs['payload']), $jobs['nombre'],$jobs['id']);
                        $this->popJobs($jobs['id'],'running',date('Y-m-d H:i:s'),'0000-00-00 00:00:00');
                    } else {
                        $this->logs->message('File downloading failed');
                    }
            }
        } catch (\Exception $exception) {
            $this->logs->message($exception->getMessage());
        }
    }

    private function formatted($cell,$campania){
        switch ($campania) {
            case 'SOAP';
             $this->rut = ($cell->getColumn() == $this->config[$campania]['rut'] && $cell->getValue() != "")?
                 $cell->getFormattedValue() : $this->rut;
             return  ($cell->getColumn() == $this->config[$campania]['rut'])? $this->rut :  "'".$cell->getFormattedValue()."'";
        default:
            return "'".$cell->getValue()."'";
        }
    }



     private function readXlsxAndInsert($filePath,$campania,$job_id){

         try {
             $it= 1;
             $spreadsheet = IOFactory::load($filePath);
             $sheet = $spreadsheet->getActiveSheet();
             $highestRow = $sheet->getHighestRow();
             $insert = $this->config[$campania]['insert'];
             foreach ($sheet->getRowIterator() as $row) {
                 $cellIterator = $row->getCellIterator();
                 $cellIterator->setIterateOnlyExistingCells(false);
                 $values= ($it != 1)? '(' : '';
                 foreach ($cellIterator as $cell) {
                      if($it == 1) continue;
                      $values.=($this->config[$campania]['limit'] ==
                          $cell->getColumn())? $this->formatted($cell,$campania).','.$job_id.')':
                          $this->formatted($cell,$campania).',';
                 }
                 $values.=($highestRow === $it++)? ';' : (($it === 2)? '' : ',') ;
                 $insert.=$values;
             }
             $this->db->query($insert);
         } catch (Exception $e) {
             $this->logs->message("Error reading file: " . $e->getMessage());
         }
     }

}