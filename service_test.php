<?php
   $data = file_get_contents("http://cal.rbge.org.uk/rbge_service_sync?since=0");
   $data = json_decode($data);
   
   echo '<pre>';
   print_r($data->events[0]);
   echo '</pre>';
?>