<?php

    namespace Src\Configuration\DB;

    use Src\Logs\Logs\Logs;
    use Symfony\Component\Yaml\Yaml;

    class DB
    {
        private $config;

        public function __construct()
        {
            $this->config = Yaml::parseFile('./config.yaml');
            $this->logs = new Logs();
        }


        public function connection()
        {
            try {
                $db = mysqli_connect($this->config[ 'db' ][ 'hostname' ], $this->config[ 'db' ][ 'username' ],
                    $this->config[ 'db' ][ 'password' ],
                    $this->config[ 'db' ][ 'database' ]);
                if (!$db) {
                    $this->logs->message("Connection failed: " . mysqli_connect_error());
                } else {
                    $this->logs->message("Connected successfully");
                    return $db;
                }
            } catch (\Exception $exception) {
                $this->logs->message($exception->getMessage());
            }

        }

        public function closeConnection($connection)
        {
            mysqli_close($connection);
        }

        public function query($query)
        {
            try {
                $con = $this->connection();
                $result = mysqli_query($con, $query);
                if ($result) {
                    $query = $this->config[ 'db' ][ 'debug' ] ? $query : ' disabled debugger';
                    $this->logs->message('Query completed : ' . $query);
                } else {
                    $this->logs->message("MYSQL Error : " . mysqli_error($con) . ' ' . $query);
                }
                $this->closeConnection($con);
                return $result;
            } catch (\Exception $exception) {
                $this->logs->message($exception->getMessage());
            }
        }
    }