<div id="belco-settings" class="wrap">
  <div>
		<h2>Settings</h2>

    <form id="belco-settings-form" action="options.php" method="post" class="belco-setup">
      <?php @settings_fields('wp_belco'); ?>
      <?php @do_settings_fields('wp_belco'); ?>

			<table class="form-table">
        <tr valign="top">
	        <th scope="row">Shop id</th>
	        <td><input type="text" name="belco_shop_id" value="<?php echo esc_attr( get_option('belco_shop_id') ); ?>" class="regular-text" /></td>
        </tr>
       
        <tr valign="top">
	        <th scope="row">Secret</th>
	        <td>
						<input type="text" name="belco_secret" value="<?php echo esc_attr( get_option('belco_secret') ); ?>" class="regular-text" />
						<p class="description">Your shop id and secret can be found in your Belco.io account, under <strong>Settings</strong> > <strong>Api keys</strong>.</p>
					</td>
        </tr>
	    </table>
			
	    <p class="submit">
	      <button type="submit" class="button button-primary">Save changes</button>
	    </p>
		</form>
	</div>
</div>