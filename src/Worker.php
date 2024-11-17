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

        public function popJobs($id, $status, $start, $end, $log)
        {
            return $this->db->query("UPDATE jobs SET status='" . $status . "', 
        start_time='" . $start . "', end_time='" . $end . "', error_log='" . $log . "' WHERE id=" . $id);
        }


        public function initJobs()
        {
            $works_exec = 0;
            try {
                while (true) {
                    $this->logs->message('Searching Jobs');
                    $job = $this->pushJobs();
                    if (count($job)> 0) {
                        $this->logs->message('Job Running ID : ' . $job[ 'id' ]);
                        $works_exec = 1;
                        $this->processJobs($job);
                        $works_exec = 0;
                    } else {
                        $this->logs->message('There is not Jobs, waiting Jobs');
                        sleep($this->config[ 'waiting' ]);
                    }
                }
            } catch (\Exception $exception) {
                $this->logs->message($exception->getMessage());
            }
        }

        private function processJobs($jobs)
        {
            try {
                switch ($jobs[ 'type' ]) {
                    case 'xls';
                        if (file_put_contents('tmp/' . basename($jobs[ 'payload' ]),
                            file_get_contents($jobs[ 'payload' ]))) {
                            $this->logs->message('Download file : ' . basename($jobs[ 'payload' ]));
                            $this->popJobs($jobs[ 'id' ], 'running', date('Y-m-d H:i:s'), '0000-00-00 00:00:00');
                            $this->readXlsxAndInsert('tmp/' . basename($jobs[ 'payload' ]), $jobs);
                        } else {
                            $this->popJobs($jobs[ 'id' ], 'error', date('Y-m-d H:i:s'),
                                '0000-00-00 00:00:00', 'File downloading failed');
                            $this->logs->message('File downloading failed');
                        }
                }
            } catch (\Exception $exception) {
                $this->logs->message($exception->getMessage());
            }
        }


        function convertDate($date) {
            if (strstr($date, "-") || strstr($date, "/"))   {
                $date = preg_split("/[\/]|[-]+/", $date);
                $date = $date[2]."-".$date[0]."-".$date[1];
                return $date;
            }
            return $date;
        }


        private function formatted($cell, $campania)
        {
            switch ($campania) {
                case 'soap';
                    $this->rut = ($cell->getColumn() == $this->config[ $campania ][ 'rut' ] && $cell->getValue() != "") ?
                        $cell->getFormattedValue() : $this->rut;
                    return ($cell->getColumn() == $this->config[ $campania ][ 'rut' ]) ? $this->rut :
                        (($cell->getColumn() != $this->config[ $campania ][ 'fecha_carga' ] ) ?
                            "'" . $cell->getFormattedValue() . "'" :  "'" . self::convertDate($cell->getFormattedValue()). "'");
                case 'coronas';
                    return ($cell->getColumn() != $this->config[ $campania ][ 'fecha_carga' ] ) ?
                     "'" . $cell->getFormattedValue() . "'" :  "'" . self::convertDate($cell->getFormattedValue()). "'";
                default:
                    $this->rut = ($cell->getColumn() == $this->config[ $campania ][ 'rut' ] && $cell->getValue() != "") ?
                        $cell->getFormattedValue() : $this->rut;
                    return ($cell->getColumn() == $this->config[ $campania ][ 'rut' ]) ? $this->rut :
                        (($cell->getColumn() != $this->config[ $campania ][ 'fecha_carga' ] ) ?
                            "'" . $cell->getFormattedValue() . "'" :  "'" . self::convertDate($cell->getFormattedValue()). "'");
            }
        }


        private function readXlsxAndInsert($filePath, $jobs)
        {

            try {
                $it = 1;
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                $insert = $this->config[ $jobs[ 'nombre' ] ][ 'insert' ];
                foreach ($sheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $values = ($it != 1) ? '(' : '';
                    foreach ($cellIterator as $cell) {
                        if ($it == 1) continue;
                        $values .= ($this->config[ $jobs[ 'nombre' ] ][ 'limit' ] ==
                            $cell->getColumn()) ? $this->formatted($cell, $jobs[ 'nombre' ]) . ',' . $jobs[ 'id' ] . ',"' . $jobs[ 'date_campaign' ] . '")' :
                            $this->formatted($cell, $jobs[ 'nombre' ]) . ',';
                    }
                    $values .= ($highestRow === $it++) ? ';' : (($it === 2) ? '' : ',');
                    $insert .= $values;
                }
                $this->dbProcess($this->db->query($insert), $jobs);

            } catch (Exception $e) {
                $this->logs->message("Error reading file: " . $e->getMessage());
                $this->popJobs($jobs[ 'id' ], 'error', date('Y-m-d H:i:s'), '0000-00-00 00:00:00', $e->getMessage());
            }
        }

        private function dbProcess($status, $jobs)
        {
            switch ($status) {
                case true;
                    $this->popJobs($jobs[ 'id' ], 'done', date('Y-m-d H:i:s'),  date('Y-m-d H:i:s'), '');
                    break;
                break;
                default:
                    $this->popJobs($jobs[ 'id' ], 'error', date('Y-m-d H:i:s'), '0000-00-00 00:00:00', 'Error while processing jobs');
                    break;
            }
        }
    }