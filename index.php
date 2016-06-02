<?php

    // may need full path in deployment
    require_once('config.php');

    // are we displaying a list or a single event?
    
    if(@$_GET['uc_id']){
        uc_display_single_event();
    }else{
        uc_display_event_list();
    }
    

function uc_display_single_event(){
    global $mysqli;
}

function uc_display_event_list(){
    global $mysqli;
    uc_head();
    uc_list_filter();
    
    // get a list of the events matching the GET params
    $today_starts = strtotime('today midnight');
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

    uc_foot();
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
    echo '<li class="uc-event">';
    if($event['image_path']){
        $image_src = "images/" . $event['uc_id'] . '_thumb.jpg';
    }else{
        $image_src = "fixme";
    }
    echo "<img src=\"$image_src\" />";
    
    echo '<div class="uc-event-content">';
    echo '<h4>';
    echo $event_data->title;
    echo '</h4>';
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
    
    // month selector
    if(@$_GET['month']) $current_month = $_GET['month'];
    else $current_month = 'all';
    $months = array('January','February','March','April','May','June',
                'July','August', 'September','October','November','December');
    
?>
    <form method="GET" action="">
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
