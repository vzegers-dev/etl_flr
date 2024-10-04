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
            $db = mysqli_connect($this->config[ 'db' ][ 'hostname' ], $this->config[ 'db' ][ 'username' ], $this->config[ 'db' ][ 'password' ],
                $this->config[ 'db' ][ 'database' ]);
            if (!$db) {
                $this->logs->message("Connection failed: " . mysqli_connect_error());
            } else {
                $this->logs->message("Connected successfully");
                return $db;
            }

        }

        public function closeConnection($connection)
        {
            mysqli_close($connection);
        }

        public function query($query)
        {
            $con = $this->connection();
            $result = mysqli_query($con, $query);
            if ($result) {
                $this->logs->message('Exito');
            } else {
                $this->logs->message("Error creating database: " . mysqli_error($con));
            }
            $this->closeConnection($con);
            return $result;
        }
    }