<?php

    // may need full path in deployment
    require_once('config.php');
    require_once('flag_sets.php');

    // the flag_set is global thing that we use all over the place
    // to define if this is events etc.
    $flag_set = @$_GET['flag_set'];
    if(!$flag_set) $flag_set = 'default';

    // are we displaying a list or a single event?
    uc_head();
    if(@$_GET['uc_id']){
        uc_display_single_event();
    }else{
        uc_display_event_list();
    }
    uc_foot();
    

function uc_display_single_event(){
    
    global $mysqli;
    global $flag_set;
    global $flag_sets;
    
    $uc_id = $_GET['uc_id'];
    if(!is_numeric($uc_id)){
        echo "<li>Error: Event ID is not numeric.</li>";
        return;
    }
    
    if( @$_GET['repeat'] && is_numeric($_GET['repeat']) ){
        $repeat = $_GET['repeat'];
    }else{
        $repeat = -1;
    }
    
    // doesn't matter which repeat of an event we pull out as they are
    // identical accept for the dates
    $sql = "SELECT * FROM events WHERE uc_id = $uc_id ORDER BY `repeat` LIMIT 1";
    $result = $mysqli->query($sql);
    if($result->num_rows < 1){
        echo "<li>Error: No event found for ID: $uc_id.</li>";
        return;
    }
    
    $event = $result->fetch_assoc();
    $event_data = json_decode($event['raw']);

    echo "<li class=\"uc-event-full\">";
    
    if($event['image_path']){
        $image_src = "http".(!empty($_SERVER['HTTPS'])?"s":"") . "://".$_SERVER['SERVER_NAME'] . "/images/" . $event['uc_id'] . '_thumb.jpg';
        echo "<img src=\"$image_src\" />";
    }
   
    echo '<h2>';
    echo $event_data->title;
    echo '</h2>';
    // echo $event_data->body;
    echo str_replace('<p>&nbsp;</p>', ' ', $event_data->body); // weird stuff with single line non breaking in modx
    
    echo '<div class="uc-event-full-details">';
    echo '<h3>Venue</h3> ' . $event_data->venue;
    echo ' @ ';
    
    $qs = 'garden_id='. $event_data->garden_id;
    $title =  $event_data->garden;
    echo javascript_link($qs, $title);
    

    if(isset($event_data->ad_start_times)){
        echo '<h3>Times</h3>';
        echo '<ul>';
        for($i = 0; $i < count($event_data->ad_start_times); $i++){
            echo '<li>';
            echo $event_data->ad_start_times[$i];
            
            if(isset($event_data->ad_end_times[$i])){
                echo ' - ' . $event_data->ad_end_times[$i];
            }
            
            echo '</li>';
            
        }
        echo '</ul>';
    }

   

    echo '<h3>Dates</h3>';
    
    echo '<ul>';
    $months = array();
    foreach($event_data->dates as $r){
        
        $date = new DateTime('@' . $r->start_timestamp);
        $date->setTimeZone(new DateTimeZone('Europe/London'));
        $date_string = $date->format('l jS F Y');
        $months[$date->format('n')] = $date->format('F');
        
        if($r->delta == $repeat){
            echo '<li class="uc-current-repeat" >';
        }else{
            echo "<li>";    
        }
        echo $date_string;
        echo "</li>";
        
    }
    echo '</ul>';
    
    $links = array();
    foreach($months as $month_id => $month){
        $links[] = javascript_link('month='. $month_id, $month);
        
    }
    foreach($event_data->cat_flags as $cat){
        if($cat->id == 'flag_142') continue; // skip the "website" flag
        $links[] = javascript_link('flags='. $cat->id, $cat->name);
    }
    
    echo '<h3>Listed in</h3>';
    echo "<p>";
    $done_first = false;
    for($i = 0; $i < count($links); $i++){
        
        $link = $links[$i];
        
        if($i > 0){
            if($i == count($links) -1){
               echo ' and ';
            }else{
                echo ', ';
            }
  
        }
        
        echo $link;
            
    }
    echo ".</p>";
    
    echo '</div>';
    
    echo "</li>";
    
}

function uc_display_event_list(){
    
    global $mysqli;
    global $flag_set;
    global $flag_sets;
    
    uc_list_filter();
    
    // get a list of the events matching the GET params
    // from timestamp
    if( @$_GET['from'] && is_numeric($_GET['from']) ){
        $today_starts = $_GET['from'];
    }else{
        $today_starts = strtotime('today midnight');
    }
    
    $sql = "SELECT * FROM events WHERE start_timestamp > $today_starts ";
    
    // garden
    if( @$_GET['garden_id'] && is_numeric($_GET['garden_id']) ){
        $sql .= " AND garden_id = " . $_GET['garden_id'];
    }
    
    // venue
    if( @$_GET['venue_id'] && is_numeric($_GET['venue_id']) ){
        $sql .= " AND venue_id = " . $_GET['venue_id'];
    }
    
    // month
    if( @$_GET['month'] && is_numeric($_GET['month']) ){
        $sql .= " AND month = " . $_GET['month'];
    }
    
    // day_of_month
    if( @$_GET['day_of_month'] && is_numeric($_GET['day_of_month']) ){
        $sql .= " AND day_of_month = " . $_GET['day_of_month'];
    }
    
    // day_of_week
    if( @$_GET['day_of_week'] && is_numeric($_GET['day_of_week']) ){
        $sql .= " AND day_of_week = " . $_GET['day_of_week'];
    }

    $flags = array();
    
    // flags from query string
    if(@$_GET['flags']){
        
        $flags = explode(',', $_GET['flags']);
        foreach($flags as $flag){
            // reject anything with space for sql injection
            $flag = trim($flag);
            if( preg_match('/\s/', $flag) ) continue;
            $sql .= " AND flags LIKE '%$flag%'";
        }
        
    }else{
        
        // no flags passed so we use the default flag_set and OR them
        // must carry one of these to be visible
        $flags = $flag_sets[$flag_set];
        
        if(count($flags)>0){
        
            $sql_or = '';
            foreach ($flags as $flag => $name) {
                 if(strlen($sql_or)>0) $sql_or .= ' OR ';
                 $sql_or .= " flags LIKE '%$flag%' ";
            }
        
            $sql .= " AND ( $sql_or ) ";
            
        }
        
        
    }
    
    
    
    $sql .= " ORDER BY start_timestamp";
    
    $result = $mysqli->query($sql);
    
    // fixme - no results
    // fixme - limit 100
    
    $month = -1;
    $day_of_week = -1;
    $day_of_month = -1;
    while($event = $result->fetch_assoc()){
        
        $date = new DateTime('@' . $event['start_timestamp']);
        $date->setTimeZone(new DateTimeZone('Europe/London'));
        
        // are we on a new month
        if($month != $event['month']){
            uc_render_month_li($date);
        }
        
        // are we on a new day
        if($day_of_week != $event['day_of_week'] || $day_of_month != $event['day_of_month']){
            uc_render_day_li($date);
        }
        
        // update where we are in the month day stakes
        $month = $event['month'];
        $day_of_week = $event['day_of_week'];
        $day_of_month = $event['day_of_month'];
        
        uc_render_event_li($event);
    }

}

function uc_render_month_li($date){
    echo '<li class="uc-month-heading"><h2>';
    echo $date->format('F Y');
    echo "</h2></li>";
}


function uc_render_day_li($date){
    echo '<li class="uc-day-heading"><h3>';
    echo $date->format('l jS');
    echo "</h3></li>";  
}


function uc_render_event_li($event){
    
    $event_data = json_decode($event['raw']);
    
    if(@$_GET['current_uc_id'] && $_GET['current_uc_id'] == $event['uc_id'] ){
        echo '<li class="uc-event uc-current-event">';
    }else{
        echo '<li class="uc-event">';
    }
    
    
    if($event['image_path']){
        $image_src =  "http".(!empty($_SERVER['HTTPS'])?"s":"") . "://".$_SERVER['SERVER_NAME'] . "/images/" . $event['uc_id'] . '_thumb.jpg';
        echo "<img src=\"$image_src\" />";
    }
    
    echo '<div class="uc-event-content">';
    echo javascript_link("uc_id={$event['uc_id']}&repeat={$event['repeat']}", '<h4>' . $event_data->title . '</h4>');

    // get a shortened event body
    $body = strip_tags($event_data->body);
    if(strlen($body) > 100){
        $body = substr($body, 0, strpos($body, ' ', 94)) . " ... ";
    }
    echo $body;
    
    $qs = "uc_id={$event['uc_id']}&repeat={$event['repeat']}";
    $title = 'Full details &gt;&gt;';
    echo javascript_link($qs, $title);

    echo '</div>';
    echo "</li>";
    
}

function javascript_link($qs, $title){
    global $flag_set;
    global $flag_sets;
    
    if($qs){
        $qs = 'flag_set=' . $flag_set . '&' . $qs;
    }else{
        $qs = 'flag_set=' . $flag_set;
    }
    return '<script type="text/javascript">ucCalSync.writeLink("' . $qs . '", "'. $title .'" )</script>';
}

function uc_head(){
    
    global $file_set;
    
    //include the style directly
    echo "\n<style type=\"text/css\">\n";
    if(file_exists('uc_'. $file_set . '_style.css')){
        readfile('uc_'. $file_set . '_style.css');
    }else{
        readfile('uc_default_style.css');
    }
    
    echo "\n</style>\n";
    
    // tiny bit of javascript to work out where we are
?>
<script type="text/javascript">

   var ucCalSync = {};
   ucCalSync.currentPage = window.location.href.split('?')[0];
   
   ucCalSync.writeLink = function(qs, title){
       document.write('<a href=\"');
       document.write(ucCalSync.currentPage);
       document.write('?');
       document.write(qs);
       document.write('" >');
       document.write(title);
       document.write('</a>');
   }
   

</script>

<?php
    
?>
    <div class="uc-whats-on-component">
        <ul>
<?php
}

function uc_foot(){
?>
        </ul>
    </div>
<?php
}

function uc_list_filter(){
    
    global $flag_set;
    global $flag_sets;
    
    // flag selector
    if(@$_GET['flags']) $current_flag = $_GET['flags'];
    else $current_flag = '';
    
    $flag_picks = $flag_sets[$flag_set];
    
?>
    <form method="GET" action="">
        <input type="hidden" name="flag_set" value="<?php echo $flag_set ?>"/>
        <select name="flags" onchange="this.form.submit()">
            <option value="">~ Show all ~</option>
<?php
        foreach($flag_picks as $flag => $label){
            if($current_flag == $flag) $selected = 'selected';
            else $selected = '';
            echo "<option $selected value=\"$flag\">$label</option>";
        }

?>
        </select>
<?php
    
    // month selector
    if(@$_GET['month']) $current_month = $_GET['month'];
    else $current_month = 'all';
    $months = array('January','February','March','April','May','June',
                'July','August', 'September','October','November','December');
    
?>
        <select name="month" onchange="this.form.submit()">
            <option value="all">Upcoming</option>
<?php
        $n = 1;
        foreach($months as $month){
            if($current_month == $n) $selected = 'selected';
            else $selected = '';
            echo "<option $selected value=\"$n\">$month</option>";
            $n++;
        }
?>
        </select>
<?php

    // garden selector
/*
    if(@$_GET['garden_id']) $current_garden_id = $_GET['garden_id'];
    else $current_garden_id = 'all';
    $gardens = array(
            '1' => 'Edinburgh Botanics',
            '2' => 'Benmore Botanics',
            '4' => 'Dawyck Botanics',
            '3' => 'Logan Botanics',
    );
*/
?>
<!--
        <select name="garden_id" onchange="this.form.submit()">
            <option value="all">All Four Gardens</option>
-->
<?php
 /*
        foreach($gardens as $garden_id => $garden){
            if($current_garden_id == $garden_id) $selected = 'selected';
            else $selected = '';
            echo "<option $selected value=\"$garden_id\">$garden</option>";
        }
*/
?>
<!--
        </select>
-->
        
    </form>

<?php
 
    
}


?>
