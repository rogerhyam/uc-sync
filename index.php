<?php

    // may need full path in deployment
    require_once('config.php');

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
        $image_src = "images/" . $event['uc_id'] . '_thumb.jpg';
        echo "<img src=\"$image_src\" />";
    }
   
    echo '<h2>';
    echo $event_data->title;
    echo '</h2>';
    echo $event_data->body;
    
    echo '<div class="uc-event-full-details">';
    echo '<h3>Venue</h3> ' . $event_data->venue;
    echo ' @ <a href="?garden_id='. $event_data->garden_id .'">'. $event_data->garden .'</a>';

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
        // echo '<a href="?month=' . $month  . '&current_uc_id='. $uc_id .'">';
        echo $date_string;
        // echo '</a>';
        echo "</li>";
        
    }
    echo '</ul>';
    
    $links = array();
    foreach($months as $month_id => $month){
        $links[] = '<a href="?month='. $month_id  .'">'. $month .'</a>';
    }
    foreach($event_data->cat_flags as $cat){
        if($cat->id == 'flag_142') continue; // skip the "website" flag
        $links[] = '<a href="?flags='. $cat->id .'">'. $cat->name  .'</a>';
    }
    
    echo "<p>";
    echo '<strong>Listed in: </strong>';
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
    
    // var_dump($event_data);
    
    echo "</li>";
    
}

function uc_display_event_list(){
    global $mysqli;
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
    
    
    // flags
    if( @$_GET['flags']){
        $flags = explode(',', $_GET['flags']);
        foreach($flags as $flag){
            // reject anything with space for sql injection
            $flag = trim($flag);
            if( preg_match('/\s/', $flag) ) continue;
            $sql .= " AND flags LIKE '%$flag%'";
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
        $image_src = "images/" . $event['uc_id'] . '_thumb.jpg';
        echo "<img src=\"$image_src\" />";
    }
    
    echo '<div class="uc-event-content">';
    echo "<a href=\"?uc_id={$event['uc_id']}&repeat={$event['repeat']}\">";
    echo '<h4>';
    echo $event_data->title;
    echo '</h4>';
    echo "</a>";
    
    // get a shortened event body
    $body = strip_tags($event_data->body);
    if(strlen($body) > 100){
        $body = substr($body, 0, strpos($body, ' ', 94)) . " ...";
    }
    echo $body;
    
    echo " <a href=\"?uc_id={$event['uc_id']}&repeat={$event['repeat']}\">Full details &gt;&gt;</a>";
    
    echo '</div>';
    
    
    echo "</li>";
    
}

function uc_head(){
    echo "\n<style type=\"text/css\">\n";
    readfile('uc_style.css');
    echo "\n</style>\n";
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
    
    // flag selector
    if(@$_GET['flags']) $current_flag = $_GET['flags'];
    else $current_flag = '';
    
    $flag_picks = array(
            'cat_136' => 'Events',
            'cat_136,flag_174' => 'Events for Families',
            'cat_136,flag_176' => 'Events for Adults',
            'cat_8' => 'Exhibitions',
            'cat_139' => 'Short Courses',
            'cat_138' => 'Professional Courses'
    );
    
?>
    <form method="GET" action="">
        <select name="flags" onchange="this.form.submit()">
            <option value="">Everything</option>
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
            <option value="all">Today</option>
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
    if(@$_GET['garden_id']) $current_garden_id = $_GET['garden_id'];
    else $current_garden_id = 'all';
    $gardens = array(
            '1' => 'Edinburgh Botanics',
            '2' => 'Benmore Botanics',
            '4' => 'Dawyck Botanics',
            '3' => 'Logan Botanics',
    );
?>            
        <select name="garden_id" onchange="this.form.submit()">
            <option value="all">All Four Gardens</option>
<?php
        foreach($gardens as $garden_id => $garden){
            if($current_garden_id == $garden_id) $selected = 'selected';
            else $selected = '';
            echo "<option $selected value=\"$garden_id\">$garden</option>";
        }

?>
        </select>
        
    </form>

<?php
 
    
}


?>
