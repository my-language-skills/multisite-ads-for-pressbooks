<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
<form name="lh_multisite_ads-backend_form" method="post" action="">
<?php wp_nonce_field( $this->namespace."-backend_nonce", $this->namespace."-backend_nonce", false ); ?>


<table class="form-table">
<tr valign="top">
<th scope="row"><label for="<?php echo $this->whitelisted_sites_field_name; ?>"><?php _e("No ads on these sites;", $this->namespace ); ?> </label></th>
<td><input type="text" name="<?php echo $this->whitelisted_sites_field_name; ?>" id="<?php echo $this->whitelisted_sites_field_name; ?>" value="<?php echo implode(",", $this->options[ $this->whitelisted_sites_field_name ]); ?>" size="60" placeholder="enter a comma separated list of the site ids e.g.: 1,51,32" />
</td>
</tr>

<tr valign="top">
<th scope="row"><label for="<?php echo $this->use_email_field_name; ?>"><?php _e("Show ads on indexes:", $this->namespace ); ?></label></th>
<td><select name="<?php echo $this->ads_on_indexes_field_name; ?>" id="<?php echo $this->use_email_field_name; ?>">'
<option value="1" <?php if ($this->options[$this->ads_on_indexes_field_name] == 1){ echo 'selected="selected"'; } ?> >Yes</option>
<option value="0" <?php if ($this->options[$this->ads_on_indexes_field_name] == 0){ echo 'selected="selected"'; } ?> >No</option>
</select> - <?php  _e("Set this to yes if you want adds to appear on the frontpage, archives, and other non singular pages.", $this->namespace );  ?></td>
</tr>
</table>


<?php submit_button(); ?>


</form>
