<?php

    require_once('config.php');
    
    // try and get the last sync time
    $last_timestamp = 0;
    if(file_exists('last_sync.txt')){
        $last_timestamp = file_get_contents('last_sync.txt');
    }
    
    // call for the data
    $data = file_get_contents("http://cal.rbge.org.uk/rbge_service_sync?since=$last_timestamp");
    $data = json_decode($data);
    
    // keep a note of the last sync time for next run
    file_put_contents('last_sync.txt', $data->till);

    // work through the events and write them to the db
    foreach($data->events as $event){
        
        // delete it if it is already in there - and all its repeats
        $mysqli->query("DELETE FROM events WHERE uc_id = {$event->id}");
        if($mysqli->error)  echo $mysqli->error;
        
        // delete images for this event
        if(file_exists('images/'. $event->id .".jpg") ){
            unlink( 'images/'. $event->id .".jpg");
            unlink( 'images/'. $event->id ."_small.jpg");
            unlink( 'images/'. $event->id ."_thumb.jpg");            
        }

        
        // create the base event for saving
        $db_event = array();
        $db_event['uc_id'] = $event->id;
        $db_event['raw'] = json_encode($event);
        $db_event['venue_id'] = $event->venue_id;
        $db_event['garden_id'] = $event->garden_id;
        $db_event['first_repeat_only'] = false;
        
        $flags = "";
        foreach($event->cat_flags as $flag){
            $flags .= " | " . $flag->id . " | " . $flag->name;
            
            // we add a flag flag for courses for which 
            // we should only show the first occurrence (i.e. you can't join
            // part way through)
            if($flag->id == 'cat_139') $db_event['first_repeat_only'] = true;
            if($flag->id == 'cat_138') $db_event['first_repeat_only'] = true;
        }
        $db_event['flags'] = $flags;
        
        // fixme - image stuff
        
        // download the image - FIXME - needs full path in deployment?
        if(isset($event->image)){
            
            $path = 'images/'. $event->id .".jpg";
            file_put_contents($path, file_get_contents($event->image));
            
            // create small ones
            makeSmall($path);
            makeThumb($path);

            $db_event['image_path'] = $path;

        }else{
            $db_event['image_path'] = "";
        }
        
        // run through all the repeats saving each
        for($i = 0; $i < count($event->dates); $i++){
            
            $db_event['repeat'] = $i;
            
            $d = $event->dates[$i];
            
            // work out a nice start time
            $start = new DateTime('@' . $d->start_timestamp);
            $start->setTimeZone(new DateTimeZone('Europe/London'));
            $db_event['month'] = $start->format('n');
            $db_event['day_of_month'] = $start->format('j');
            $db_event['day_of_week'] = $start->format('N');
            $db_event['start_timestamp'] = $d->start_timestamp;
            $db_event['end_timestamp'] = $d->end_timestamp;
            
            save_event($db_event);
            
            // break after the first one if this is a course
            if($db_event['first_repeat_only']) break;

        }

    }
    
    
    
function save_event($db_event){
    
    global $mysqli;
    
    $stmt = $mysqli->prepare("INSERT INTO `events` 
        (`uc_id`, `repeat`, `venue_id`, `image_path`, `flags`, `start_timestamp`, `end_timestamp`, `month`, `day_of_week`, `day_of_month`, `raw`, `garden_id`) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?);");
    $stmt->bind_param(
        'iiissiiiiisi',
        $db_event['uc_id'],
        $db_event['repeat'],
        $db_event['venue_id'],
        $db_event['image_path'],
        $db_event['flags'],
        $db_event['start_timestamp'],
        $db_event['end_timestamp'],
        $db_event['month'],
        $db_event['day_of_week'],
        $db_event['day_of_month'],
        $db_event['raw'],
        $db_event['garden_id']
    );
    $stmt->execute();
    if($mysqli->error)  echo $mysqli->error;
    $stmt->close();
    
}

function makeSmall($path, $max_dimension = 800){
    
    $org_info = getimagesize($path);
    $aspect = $org_info[0]/$org_info[1];
    if($aspect < 1){
        $h = $max_dimension;
        $w = $max_dimension * $aspect;                
    }else{
        $h = $max_dimension / $aspect;
        $w = $max_dimension;  
    }
    
    $rsr_org = imagecreatefromjpeg($path);
    $rsr_scl = imagescale($rsr_org, $w, $h);
    imagejpeg($rsr_scl, str_replace('.jpg', '_small.jpg', $path));
    imagedestroy($rsr_org);
    imagedestroy($rsr_scl);
    
}

/*
    pinched from here:
    http://stackoverflow.com/questions/2686000/use-php-to-create-thumbnails-cropped-to-square
*/
function makeThumb( $filepath , $thumbSize=200 ){
    
  $srcFile = $filepath;
  $thumbFile = str_replace('.jpg', '_thumb.jpg', $filepath);
  
  $src = imagecreatefromjpeg( $srcFile );
 
 /* Determine the Image Dimensions */
  $oldW = imagesx( $src );
  $oldH = imagesy( $src );
  
   /* Calculate the New Image Dimensions */
   $limiting_dim = 0;
    if( $oldH > $oldW ){
     /* Portrait */
      $limiting_dim = $oldW;
    }else{
     /* Landscape */
      $limiting_dim = $oldH;
    }
   /* Create the New Image */
    $new = imagecreatetruecolor( $thumbSize , $thumbSize );
   /* Transcribe the Source Image into the New (Square) Image */
    imagecopyresampled( $new , $src , 0 , 0 , ($oldW-$limiting_dim )/2 , ( $oldH-$limiting_dim )/2 , $thumbSize , $thumbSize , $limiting_dim , $limiting_dim );
  
  imagejpeg( $new , $thumbFile );

  imagedestroy( $new );
  imagedestroy( $src );

    
}

?>