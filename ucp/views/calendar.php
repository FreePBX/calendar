<div id="calcontainer-<?php echo $id?>" class="calcontainer">
<?php
  if($listmode){
    echo '<div id="agenda-'.$id.'" data-calid="'.$id.'"></div>';
  }else{
    echo '<div id="calendar-'.$id.'" data-calid="'.$id.'"></div>';
  }

?>
</div>
