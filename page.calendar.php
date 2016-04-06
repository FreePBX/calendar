<?php

$content = load_view(__DIR__.'/views/calendar.php',array());
?>
<script>
  var timezone = "<?php echo date_default_timezone_get()?>";
</script>
<div class="container-fluid">
  <h1>
    <span><?php echo _('Calendar')?></span>
    <a href="#" class="btn btn-default pull-right" data-toggle="modal" data-target="#addEvent"><i class="fa fa-plus"></i> <?php echo _('Add Event')?></a>
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
