<?php
$cal = FreePBX::Calendar();
if(isset($_REQUEST['view']) && $_REQUEST['view'] == 'form'){
  $vars = array();
  $id = isset($_REQUEST['id'])?$_REQUEST['id']:'';
  $event = $cal->getGroup($id);
  $vars['eventopts'] = $cal->getEventOptions($id);
  $vars['description'] = $event['description'];
  $vars['id'] = $id; 
  $content = load_view(__DIR__.'/views/calendargroups.php',$vars);
}else{
  $content = load_view(__DIR__.'/views/calendargroupgrid.php',array());
}
?>
<div class="container-fluid">
  <h1>
    <span><?php echo _('Calendar Event Groups')?></span>
  </h1>
</div>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div class="display full-border">
						<?php echo $content?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
