<?php
namespace Src\Configuration\Inotify;
use Src\Logs\Logs\Logs;

require 'src/Logs.php';

class Inotify
{
   public $log;
   public function __construct(){
       if (!extension_loaded('inotify')) {
           die("The inotify extension is not loaded.\n");
       }
       $this->log = new Logs();
   }

   public function start(){

       $directory = '/var/www/html/inotify/';
       $inotifyInstance = inotify_init();
       $watchDescriptor = inotify_add_watch($inotifyInstance, $directory,
           IN_CREATE | IN_MODIFY | IN_DELETE | IN_MOVE);
       if ($watchDescriptor === false) {
           die("Failed to add watch on $directory\n");
       }
       echo "Watching $directory for changes...\n";

// Loop to monitor the events
       while (true) {
           // Read the events
           $events = inotify_read($inotifyInstance);

           if ($events === false) {
               die("Failed to read events\n");
           }

           // Process each event
           foreach ($events as $event) {
               $eventName = '';
               // Determine the type of event
               if ($event['mask'] & IN_CREATE) {
                   $eventName = "created";
                   $this->log->message("Archivo cargado en ");
               } elseif ($event['mask'] & IN_MODIFY) {
                   $eventName = "modified";
               } elseif ($event['mask'] & IN_DELETE) {
                   $eventName = "deleted";
               } elseif ($event['mask'] & IN_MOVE) {
                   $eventName = "moved";
       }else{
                   $eventName = "all";
               }

               // Get the name of the file affected
               $fileName = $event['name'];
               $PID = getmypid();
               echo "PID: '$PID ' File '$fileName' was $eventName.\n";
           }
       }

// Clean up
       inotify_rm_watch($inotifyInstance, $watchDescriptor);
       fclose($inotifyInstance);
   }






}