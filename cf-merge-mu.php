<?php
/*
Plugin Name: CF Merge MU Blog Content
Plugin URI: http://crowdfavorite.com
Description: Simplified functionality for merging MU blogs into 1 blog
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com 
*/

// Definitions

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);
define('CFMMU_INCREMENT', 20);
define('CFMMU_DIR', plugin_dir_path(__FILE__));
define('CFMMU_DIR_URL', trailingslashit(plugins_url(basename(dirname(__FILE__)))));


// Init Functions

function cfmmu_request_handler() {
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cfmmu_import':
				if (!empty($_POST['blog_id']) && (!empty($_POST['offset']) || $_POST['offset'] == 0)) {
					$blog_id = stripslashes($_POST['blog_id']);
					$offset = stripslashes($_POST['offset']);
					$type = stripslashes($_POST['type']);
					cfmmu_import($blog_id, $offset, $type);
				}
				die();
				break;
		}
	}
}
add_action('init', 'cfmmu_request_handler');

function cfmmu_resources() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cfmmu_admin_js':
				cfmmu_admin_js();
				die();
				break;
		}
	}
}
add_action('init', 'cfmmu_resources', 1);

// JS Functions

function cfmmu_admin_js() {
	header('Content-type: text/javascript');
	?>
	;(function($) {
		$(function() {
			$(".cfmmu-import-posts").live('click', function() {
				if (confirm('Do you have the console open so you can watch the AJAX requests?')) {
					var _this = $(this);
					var id = _this.attr('id').replace('cfmmu-import-posts-', '');
					$("#cfmmu-progress-"+id).show();
					$(".cfmmu-import").attr('disabled', 'disabled');
					$("#cfmmu-progress-display-"+id).html("Processing posts . ");
					cfmmu_do_batch(id, 0, 'posts');
				}
			});
			$(".cfmmu-import-pages").live('click', function() {
				if (confirm('Do you have the console open so you can watch the AJAX requests?')) {
					var _this = $(this);
					var id = _this.attr('id').replace('cfmmu-import-pages-', '');
					$("#cfmmu-progress-"+id).show();
					$(".cfmmu-import").attr('disabled', 'disabled');
					$("#cfmmu-progress-display-"+id).html("Processing pages . ");
					cfmmu_do_batch(id, 0, 'pages');
				}
			});
			$(".cfmmu-import-attachments").live('click', function() {
				if (confirm('Do you have the console open so you can watch the AJAX requests?')) {
					var _this = $(this);
					var id = _this.attr('id').replace('cfmmu-import-attachments-', '');
					$("#cfmmu-progress-"+id).show();
					$(".cfmmu-import").attr('disabled', 'disabled');
					$("#cfmmu-progress-display-"+id).html("Processing attachments . ");
					cfmmu_do_batch(id, 0, 'attachments');
				}
			});
			
			function cfmmu_do_batch(blogId, offset_amount, type) {
				$("#cfmmu-info-"+blogId).removeClass('cfmmu-processed');
				
				$.post('<?php echo admin_url(); ?>', {
					cf_action:'cfmmu_import',
					blog_id: blogId,
					offset: offset_amount,
					type: type
				}, function(r) {
					if (r.status == 'finished') {
						$("#cfmmu-progress-"+blogId).hide();
						$("#cfmmu-info-"+blogId).addClass('cfmmu-processed');
						$(".cfmmu-import").attr('disabled', '');
						return;
					}
					else {
						$("#cfmmu-progress-display-"+blogId).html(r.status);
						cfmmu_do_batch(blogId, r.next_offset, r.type);
					}
				}, 'json');
			}
		});
	})(jQuery);
	<?php
	die();
}
if (isset($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
	wp_enqueue_script('cfmmu_admin_js', admin_url('?cf_action=cfmmu_admin_js'), array('jquery'), 1.0);
}

// Menu Functions

function cfmmu_admin_menu() {
	if (current_user_can('manage_options')) {
		add_submenu_page(
			'wpmu-admin.php',
			__('CF Merge MU Blog', 'cfdbl')
			, __('CF Merge MU Blog', 'cfdbl')
			, 10
			, basename(__FILE__)
			, 'cfmmu_form'
		);
	}
}
add_action('admin_menu', 'cfmmu_admin_menu');


// Admin Display Functions

function cfmmu_form() {
	global $current_site, $blog_id;
	$bloglist = get_blog_list(0,'all');
	?>
	<style type="text/css">
		.cfmmu-processed {
			background-color:#E6DB55;
		}
	</style>
	<div class="wrap">
		<?php echo screen_icon().'<h2>CF Merge MU Blog</h2>'; ?>
		<table class="widefat" style="width:400px;">
			<thead>
				<tr>
					<th>ID</th>
					<th>Blog Name</th>
					<th style="text-align:center;">Import Posts</th>
					<th style="text-align:center;">Import Pages</th>
					<th style="text-align:center;">Import Attachments</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>ID</th>
					<th>Blog Name</th>
					<th style="text-align:center;">Import Posts</th>
					<th style="text-align:center;">Import Pages</th>
					<th style="text-align:center;">Import Attachments</th>
				</tr>
			</tfoot>
			<tbody>
			<?php
			if (is_array($bloglist) && !empty($bloglist)) {
				foreach ($bloglist as $blog) {
					if ($blog['blog_id'] == $blog_id) { continue; }
					?>
					<tr id="cfmmu-info-<?php echo $blog['blog_id']; ?>" class="cfmmu-info">
						<td>
							<?php echo $blog['blog_id']; ?>
						</td>
						<td>
							<a href="<?php echo trailingslashit(get_blogaddress_by_domain($blog['domain'], $blog['path'])).'wp-admin'; ?>"><?php echo untrailingslashit($blog['domain']).$blog['path']; ?></a>
						</td>
						<td>
							<input type="button" class="button cfmmu-import-posts cfmmu-import" id="cfmmu-import-posts-<?php echo esc_attr($blog['blog_id']); ?>" value="Import Posts" />
						</td>
						<td>
							<input type="button" class="button cfmmu-import-pages cfmmu-import" id="cfmmu-import-pages-<?php echo esc_attr($blog['blog_id']); ?>" value="Import Pages" />
						</td>
						<td>
							<input type="button" class="button cfmmu-import-attachments cfmmu-import" id="cfmmu-import-attachments-<?php echo esc_attr($blog['blog_id']); ?>" value="Import Attachments" />
						</td>
					</tr>
					<tr id="cfmmu-progress-<?php echo $blog['blog_id']; ?>" class="cfmmu-progress" style="display:none;">
						<td colspan="5">
							<span class="cfmmu-progress-display" id="cfmmu-progress-display-<?php echo $blog['blog_id']; ?>">
								Processing .
							</span>
						</td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
	</div>
	<?php
}


// Processing Functions

function cfmmu_import($export_blog_id, $offset = 0, $type) {
	global $blog_id;
	$dots = ' . ';
	$ids = array();
	
	// Get the blog ID that the content is being imported to
	$import_blog_id = $blog_id;

	// Switch to the blog we are getting posts from
	switch_to_blog($export_blog_id);

	$exclude_posts = get_option('cfmmu_exclude_posts', true);
	if (!is_array($exclude_posts)) {
		$exclude_posts = array();
	}
	
	switch ($type) {
		case 'posts':
			if ($offset == 0) {
				// Update all of the users so they have permissions on the imported blog
				cfmmu_update_users($import_blog_id, $export_blog_id);
			}
			
			// Get the posts, but not the posts we have already
			$posts = new WP_Query(array(
				'post_type' => 'post',
				'showposts' => CFMMU_INCREMENT,
				'orderby' => 'date',
				'post_status' => 'publish',
				'offset' => $offset
			));
			break;
		case 'pages':
			// Get the pages, but not the pages we have already
			$posts = new WP_Query(array(
				'post_type' => 'page',
				'showposts' => CFMMU_INCREMENT,
				'orderby' => 'date',
				'offset' => $offset
			));
			break;
		case 'attachments':
			// Get the pages, but not the pages we have already
			$posts = new WP_Query(array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'showposts' => CFMMU_INCREMENT,
				'orderby' => 'date',
				'offset' => $offset
			));
			break;
	}

	// Cleanup all of the files
	foreach (glob('/tmp/cfmmu*') as $filename) {
		@unlink($filename);
	}

	// If we have posts to import, lets do it
	if ($posts->have_posts()) {
		while ($posts->have_posts()) {
			$posts->the_post();
			$ids[] = get_the_ID();
		}
		
		if (is_array($ids) && !empty($ids)) {
			// Include all of the files needed
			if (!class_exists('WP_Import')) {
				define('WP_LOAD_IMPORTERS', true);
				include_once(trailingslashit(CFMMU_DIR).'includes/wordpress.php');
			}
			if (!function_exists('cfdbl_export')) {
				include_once(trailingslashit(CFMMU_DIR).'includes/cfdbl-wxr.php');
			}
			if (!function_exists('post_exists')) {
				/** WordPress Administration API */
				require_once(ABSPATH . 'wp-admin/includes/admin.php');
			}
			
			// Get the XML for the page IDs passed in
			ob_start();
			$xml = cfdbl_export($ids);
			$content = ob_get_contents();
			ob_end_clean();
			unset($content);
			
			// Build the filename for where to push the file
			$filename = "/tmp/cfmmu-export_".date("Y-m-d-H-i-s").'_blog-'.$export_blog_id.'-offset-'.$offset.'-'.$type.'.xml';

			// Write the XML to the file
			$handle = fopen($filename,'w+');
			fwrite($handle,$xml);
			fclose($handle);
			
			$exclude_posts = array_merge($exclude_posts, $ids);
			update_option('cfmmu_exclude_posts', $exclude_posts);

			// Go back to the current blog so we can do the import
			restore_current_blog();
			
			ob_start();
			$wp_import->import_file($filename);
			$data = ob_get_contents();
			ob_end_clean();
			unset($data);
			
			if ($offset > 0) {
				for ($i = 0; $i <= ($offset/CFMMU_INCREMENT); $i++) {
					$dots .= ' . ';
				}
			}
			$next_offset = intval($offset);
			$next_offset++;
			@unlink($filename);
			echo(json_encode(array('status' => 'Processing '.$type.' '.$dots, 'next_offset' => $next_offset, 'type' => $type)));
			return;
		}
	}

	// We are finished

	// Cleanup all of the files
	// foreach (glob('/tmp/cfmmu*') as $filename) {
	// 	@unlink($filename);
	// }
	
	// Cleanup the exclude array
	update_option('cfmmu_exclude_posts', array());

	// Return the proper JSON string telling the AJAX we are finished
	echo(json_encode(array('status' => 'finished')));
	return true;
}

function cfmmu_update_users($import_blog_id, $export_blog_id) {
	// Since WP 3.0 uses complicated blog id names, lets check them to make sure
	$export_capabilities = '';
	$import_capabilities = '';

	if ($import_blog_id == 1) {
		$import_capabilities = 'wp_capabilities';
	}
	else {
		$import_capabilities = 'wp_'.$import_blog_id.'_capabilities';
	}
	if ($export_blog_id == 1) {
		$export_capabilities = 'wp_capabilities';
	}
	else {
		$export_capabilities = 'wp_'.$export_blog_id.'_capabilities';
	}

	// Update the users, and give them capabilities on the blog being imported to
	$users = get_users_of_blog($export_blog_id);

	foreach ($users as $user) {
		$blog_capabilities = get_user_meta($user->ID, $export_capabilities);
		$main_capabilities = get_user_meta($user->ID, $import_capabilities);

		if ((!is_array($main_capabilities) || empty($main_capabilities)) && (is_array($blog_capabilities) && !empty($blog_capabilities))) {
			update_usermeta($user->ID, $import_capabilities, $blog_capabilities[0]);
		}
	}
}


/**
 * This function adds the current blog name as a category to the posts being exported.  This is for easier sorting on the imported
 * blog side
 *
 * @param string $taxonomy | Currently built taxonomy string
 * @param string $post_id | Post ID being modified
 * @return string | Modified taxonomy string with the blog name as a category
 */
function cfmmu_export_add_category($taxonomy) {
	global $blog_id;
	
	$details = get_blog_details($blog_id);

	$blogname = $details->blogname;
	$slug = sanitize_title($blogname);
	$name = $blogname;

	// Standard category setup
	$taxonomy .= "\n\t\t<category><![CDATA[".$slug."]]></category>\n";
	$taxonomy .= "\n\t\t<category domain=\"category\" nicename=\"{".$blogname."}\"><![CDATA[".$blogname."]]></category>\n";
	
	return $taxonomy;
}
add_filter('cfdbl-export-taxonomy', 'cfmmu_export_add_category', 10, 2);

function cfmmu_export_add_extra_info($extra) {
	global $blog_id;
	$details = get_blog_details($blog_id);
	
	$blogname = $details->blogname;
	$slug = sanitize_title($blogname);

	$extra .= '
		<wp:postmeta>
			<wp:meta_key>_cf_related_category</wp:meta_key>
			<wp:meta_value>'.wxr_cdata($slug).'</wp:meta_value>
		</wp:postmeta>
	';
	
	return $extra;
}
add_filter('wxr_export_extras', 'cfmmu_export_add_extra_info');

?>