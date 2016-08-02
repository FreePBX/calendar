<?php
$cal = FreePBX::Calendar();
echo $cal->showPage();
//include __DIR__."/views/grid.php";
//$cal = FreePBX::Calendar();
//$content = load_view(__DIR__.'/views/calendar.php',array());
//$content .= load_view(__DIR__.'/views/eventModal.php',array('cal'=>$cal));
?>
<!--
<script>
  var timezone = "<?php echo FreePBX::View()->getTimezone();?>";
  var daysOfWeek = ['<?php echo _("Sunday")?>', '<?php echo _("Monday")?>', '<?php echo _("Tuesday")?>', '<?php echo _("Wednesday")?>','<?php echo _("Thursday")?>', '<?php echo _("Friday")?>', '<?php echo _("Saturday")?>'];
  var daysOfWeekShort = ['<?php echo _("Sun")?>', '<?php echo _("Mon")?>', '<?php echo _("Tue")?>', '<?php echo _("Wed")?>','<?php echo _("Thu")?>', '<?php echo _("Fri")?>', '<?php echo _("Sat")?>'];
</script>
<div class="container-fluid">
  <h1>
    <span><?php echo _('Calendar Management')?></span>
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
<script type="text/javascript">
var destinations = <?php echo json_encode(FreePBX::Modules()->getDestinations())?>;
</script>
-->
