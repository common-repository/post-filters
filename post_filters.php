<?php

/*
Plugin Name: Post Filters
Plugin URI: http://naatan.com/category/wordpress/plugins/post-filters/
Description: Gives the ability to have posts skip the frontpage and to stick a post to the top of it's categories
Version: 1.0.1
Author: Nathan Rijksen
Author URI: http://naatan.com/
*/

add_filter('posts_where','post_filters_modify_whereclause');
add_action('save_post','post_filters_edit_post_process');
add_action('admin_menu', 'post_filters_define_metabox');
add_filter('posts_orderby', 'post_filters_modify_orderby');
add_filter('posts_fields', 'post_filters_modify_fields');
register_activation_hook( __FILE__, 'post_filters_activate' );
register_deactivation_hook( __FILE__, 'post_filters_deactivate' );

function post_filters_define_metabox() {
	if( function_exists( 'add_meta_box' )) {
		add_meta_box('postfiltersdiv', 'Post Filters', 'post_filters_add_metabox', 'post');
	} else {
		add_action('dbx_post_sidebar', 'post_filters_add_sidebar_box');
	}
}

function post_filters_add_sidebar_box() {
	?>
	<fieldset id="postfiltersdiv" class="dbx-box">
		<h3 class="dbx-handle">Post Filters</h3> 
		<div class="dbx-content">
			<?php post_filters_add_metabox(); ?>
		</div>
	</fieldset>
	<?php
}

function post_filters_add_metabox() {
	global $post;
	$checked_skip = $post->skip_frontpage=='true' ? 'checked="checked"' : '';
	$checked_stick = $post->category_stick=='true' ? 'checked="checked"' : '';
	?>
	<label for="post_filter_skip_frontpage" class="selectit">
		<input type="checkbox" id="post_filter_skip_frontpage" name="post_filter_skip_frontpage" value="true" <?php echo $checked_skip; ?>/>&nbsp;Skip Frontpage
	</label>
	&nbsp;&nbsp;&nbsp;
	<label for="post_filter_stick_categories" class="selectit">
		<input type="checkbox" id="post_filter_stick_categories" name="post_filter_stick_categories" value="true" <?php echo $checked_stick; ?>/>&nbsp;Stick to Categories
	</label>
	<?php
}

function post_filters_modify_whereclause($clause) {
	if (!is_home()) return $clause;
	return $clause . "AND skip_frontpage='false'";
}

function post_filters_modify_fields($fields) {
	if (!is_category()) return $fields;
	global $wpdb;
	return $fields.", $wpdb->posts.category_stick";
}

function post_filters_modify_orderby($orderby) {
	if (!is_category()) return $orderby;
	global $wpdb;
	return "FIELD($wpdb->posts.category_stick,'true') DESC, ".$orderby;
}

function post_filters_edit_post_process($pid) {
	global $wpdb;
	$skipstatus = empty($_POST['post_filter_skip_frontpage']) ? 'false' : 'true';
	$stickstatus = empty($_POST['post_filter_stick_categories']) ? 'false' : 'true';
	$wpdb->query("UPDATE $wpdb->posts SET skip_frontpage='".$skipstatus."', category_stick='".$stickstatus."' WHERE ID='".$pid."'");
}

function post_filters_activate() {
	
	global $wpdb;
		
	$fields = $wpdb->get_results("DESCRIBE $wpdb->posts");
	foreach ($fields AS $field) {
		if ($field->Field=='skip_frontpage') $skipexists = true;
		if ($field->Field=='category_stick') $stickexists = true;
	}
	
	if (empty($skipexists)) $wpdb->query("ALTER TABLE $wpdb->posts ADD `skip_frontpage` ENUM( 'true', 'false' ) NOT NULL default 'false'");
	if (empty($stickexists)) $wpdb->query("ALTER TABLE $wpdb->posts ADD `category_stick` ENUM( 'true', 'false' ) NOT NULL default 'false'");
	
}

function post_filters_deactivate() {
	
	global $wpdb;
		
	$fields = $wpdb->get_results("DESCRIBE $wpdb->posts");
	foreach ($fields AS $field) {
		
		if ($field->Field=='skip_frontpage')
			$wpdb->query("ALTER TABLE $wpdb->posts DROP `skip_frontpage`");
			
		if ($field->Field=='category_stick')
			$wpdb->query("ALTER TABLE $wpdb->posts DROP `category_stick`");
		
	}
	
}


?>