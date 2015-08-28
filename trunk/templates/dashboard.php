<div id="belco-container">
	<iframe src="//<?php echo BELCO_HOST; ?><?php echo $page; ?>" id="belco-frame"></iframe>
</div>
<?php if (!$installed) : ?>
	<form id="belco-install-form" action="options.php" method="post" style="display: none;">
	  <?php @settings_fields('wp_belco'); ?>
	  <?php @do_settings_fields('wp_belco'); ?>
	</form>
<?php endif; ?>