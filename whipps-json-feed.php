<?php
/*
Plugin Name: Whipps JSON feed
Plugin URI: http://wordpress.org/extend/plugins/whipps-json-feed/
Description: Provides Whipps iPhone application with feeds in JSON form
Version: 1.0
Author: Whipps
Author URI: http://www.whipps.org/
*/

function whipps_init() {
	add_action('template_redirect', 'template_redirect');
	add_filter('query_vars', 'query_vars');
}
/*
function whipps_activation() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function whipps_deactivation() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}
*/
function template_redirect() {
  	$method = get_query_var('whipps');
    
    if (empty($method)) {
      return false;
    } else if ($method == "on") {
		json_feed();
    	exit;
    }
}

function query_vars($wp_vars) {
    // This makes our variables available from the get_query_var WP function
  $vars = array(
  	'whipps',
    'feed',
	'version',
  	'paged',
  	'sub',
  );
  	
	return array_merge($wp_vars, $vars);
}
  
add_action('init', 'whipps_init');
#register_activation_hook(__FILE__, 'whipps_activation');
#register_deactivation_hook(__FILE__, 'whipps_deactivation');

add_filter('query_vars', 'json_feed_queryvars');

function json_feed_queryvars($qvars)
{
	$qvars[] = 'jsonp';
	$qvars[] = 'date_format';
	$qvars[] = 'remove_uncategorized';
	return $qvars;
}

add_action('admin_init', 'whipps_options_init');
add_action('admin_head', 'whipps_admin_head');
add_action('admin_menu', 'whipps_options_add_page');


function json_feed()
{
	$output = array();

	if (have_posts()) {
		global $wp_query;
		
		// Whipps support check
		if ($_GET['version'] == 'check') {
			echo '1.0'; die;
		}
		
		// First call on app launch - show categories / also used to show sub-categories
		if ($_GET['paged'] == '0') {
			// Are we asked for sub-categories?
			if ($_GET['sub']) {
				$categories = get_categories(array('parent' => $_GET['sub'], 'hide_empty' => 0));
				foreach ($categories as $key => $cat) {
					//No parent? - kick out
					if ($cat->parent == 0) {
						unset($categories[$key]);
					}
				}
			} else {
				// Show all categories
				$categories = get_categories(array('hide_empty' => 0, 'hierarchical' => 1));
				foreach ($categories as $key => $cat) {
					//Has parent -> kick out
					if ($cat->parent > 0) {
						unset($categories[$key]);
					}
				}
			}
			// Filter unwanted / modify "json_feed_remove_uncategorized" below
			$categories = array_filter($categories, 'json_feed_remove_uncategorized');

			foreach ($categories as $cat) {
				if (get_category_children($cat->cat_ID) != "") {
					$cat_array[] = json_feed_format_category($cat, true);
				} else { 
					$cat_array[] = json_feed_format_category($cat, false);
				}
			}

			$whipps_cfg = get_whipps_options();
			// JSON output - list of categories		
			$output['ResultSet'] = array
			(
				'Base' => get_option('category_base'),
				'InfoPage' => $whipps_cfg['infoPage'],
				'Result' => $cat_array
			);

		} else {
			// JSON output - header and list of posts
			$output['ResultSet'] = array
			(
				//'base' => get_option('category_base'),
				'resultsAvailable' => (int) $wp_query->found_posts,
				'resultsReturned' => (int) $wp_query->post_count,
				'pagesAvailable' => (int) $wp_query->max_num_pages,
				'pageReturned' => (int) $_GET['paged']
			);

			// Go through all the posts out there...
			while (have_posts()) {
		        the_post();
				// Make sure post is not hidden
				if (!in_category(json_feed_get_hidden())) {
					$results = array();

					// What to show? Let's get all posts with image attachment
					$photos = get_children( 
								array(	'post_parent' => get_the_ID(), 
										'post_type' => 'attachment', 
										'post_mime_type' => 'image', 
										'post_status' => null,
										'numberposts' => -1,
										'order' => 'ASC', 
										'orderby' => 'menu_order ID') 
							);

					if ($photos) {
						foreach ($photos as $photo) {
							$url = wp_get_attachment_url($photo->ID);
							$path = parse_url($url);
						
							$thumb_url = wp_get_attachment_thumb_url($photo->ID);
							list($width, $height, $type, $attr) = getimagesize($_SERVER['DOCUMENT_ROOT'].$path['path']);
							$url_array[] = $url; $url_array[] = $width; $url_array[] = $height;
						}

						// Add current photo to JSON output array
						$output['ResultSet']['Result'][] = array
				    	(
							'id' => (int) get_the_ID(),
				        	'permalink' => get_permalink(),
				        	'title' => get_the_title(),
				        	// Attachment attribs
				        	'date' => get_the_time(json_feed_date_format()),
							'designed-by' => json_feed_designer(),
							'web-url' => json_feed_url(),
							'url' => $url_array[0],
							'thumb' => $thumb_url,
							'width' => $url_array[1],
							'height' => $url_array[2],
				    		);	

						unset($url_array);
					}
				}
			}
		}
	}

    if (get_query_var('jsonp') == '')
    {
       	header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
       	echo json_encode($output);
    } else {
       	header('Content-Type: application/javascript; charset=' . get_option('blog_charset'), true);
       	echo get_query_var('jsonp') . '(' . json_encode($output) . ')';
    }
}

function json_feed_date_format()
{
  if (get_query_var('date_format'))
  {
      return get_query_var('date_format');
  } else {
      return 'F j, Y H:i';
  }
}  

function json_feed_check_new($category)
{
	$has_new = "NO";
	$whipps_cfg = get_whipps_options();

	if ((is_numeric($whipps_cfg['days'])) && ($whipps_cfg['days'] >= 1)) {
		$days = $whipps_cfg['days'];
	} else {
		$days = 3;
	}
	
	$recent = new WP_Query("cat=".$category->cat_ID."&showposts=1");
	    
	while($recent->have_posts()) {
		$recent->the_post();
		// Mark posts 3 days old as new
		if ((time()-get_the_time('U')) <= ($days*86400)) {
			$has_new = "YES";
		}
	}
	
	return $has_new;
}

function json_feed_categories()
{
    $categories = get_the_category();
    if (is_array($categories))
    {
        $categories = array_values($categories);
        if (get_query_var('remove_uncategorized'))
        {
            $categories = array_filter($categories, 'json_feed_remove_uncategorized');
        }

        return array_map('json_feed_format_category', $categories);
    } else {
        return array();
    }
}

function json_feed_get_hidden()
{
    // Array of categories that we do not want to show within iPhone app
	$whipps_cfg = get_whipps_options();

	if (is_array($whipps_cfg['cats'])) {
		return array_keys($whipps_cfg['cats']);
	}
	return array();
}

function json_feed_remove_uncategorized($category)
{
    // Array of categories that we do not want to show within iPhone app 
    $whipps_cfg = get_whipps_options();

    if ($whipps_cfg['cats'][$category->cat_ID] && $whipps_cfg['cats'][$category->cat_ID] == "on")
    {
        return false;
    } else {
        return true;
    }
}

function json_feed_format_category($category, $sub = false)
{
    if ($sub) {
		return array (
			'id' => $category->cat_ID,
			'name' => $category->cat_name,
			'slug' => $category->category_nicename,
			'new' => json_feed_check_new($category),
			'sub' => 1
		);    	
    } else {
    	return array (
			'id' => $category->cat_ID,
			'name' => $category->cat_name,
			'slug' => $category->category_nicename,
    		'new' => json_feed_check_new($category),
			'count' => $category->category_count,
		);
    }
}

function json_feed_tags()
{
    $tags = get_the_tags();
    if (is_array($tags))
    {
        $tags = array_values($tags);
        return array_map('json_feed_format_tag', $tags);
    } else {
        return array();
    }
}

function json_feed_format_tag($tag)
{
    return array
    (
        'id' => (int) $tag->term_id,
        'title' => $tag->name,
        'slug' => $tag->slug
    );
}

function json_feed_designer()
{
	// Optional post's custom key for photographer
	$designer = get_post_meta(get_the_ID(), 'designed-by', $single = true);
	
    if ($designer)
    {
	    return $designer;
    } else {
        return 'Unknown';
    }
}

function json_feed_url()
{
	$url = get_post_meta(get_the_ID(), 'web-url', $single = true);
	
    if ($url)
    {
	    return $url;
    } else {
        return 'Unknown';
    }
}

function json_feed_customKey()
{
	$key = get_post_meta(get_the_ID(), 'designed-by', $single = true);

    if ($key)
    {
	    return array
	    (
	        'url' => $key,
	    );
    } else {
        return array();
    }
}

/************* OPTIONS ******************
****************************************/

function whipps_options_init(){
	register_setting('whipps_options', 'whipps');	
}

function whipps_admin_head()
{
	$plugindir_node = dirname(plugin_basename(__FILE__));	
	$plugindir_url 	= get_bloginfo('wpurl') . "/wp-content/plugins/". $plugindir_node;
		?>
	<link rel="stylesheet" href="<?php echo $plugindir_url ?>/style.css"
		type="text/css" media="screen" />
	<?php
}
	
// Add menu page
function whipps_options_add_page() {
	add_options_page('Whipps Options', 'Whipps Options', 'manage_options', 'whipps_options', 'whipps_options_do_page');
}

// Draw the menu page itself
function whipps_options_do_page() {
		global $wpdb;
		$categories = get_categories('hide_empty=0&orderby=name&order=ASC');
	?>
	<div class="wrap">
		<h2>Whipps Options</h2>
		<form method="post" action="options.php">
			<?php settings_fields('whipps_options'); ?>
			<?php $options = get_option('whipps'); ?>
			<?php $whipps_cfg = get_whipps_options(); ?>

			<p>Enter full URL to your blog info page (Top link for your blog displayed within Whipps app). 
			We recommend using <a href="http://www.bravenewcode.com/products/wptouch/" target="_blank">WP Touch</a> for best look & feel!
			</p>
			
				<table class="widefat" width="80%" cellpadding="0" cellspacing="2" border="0">
				<thead>
		        <tr>
		        	<th class="cat-id" scope="col">Blog info URL (http://www.myblog.com/info)</th>
		        </tr>
				</thead>
					<tr>
						<td class="cat-id">
							<label for="infoPage">Blog info full URL</label>&nbsp;
							<input type="text" size="50" name="whipps[infoPage]" id="infoPage" value="<?php echo $whipps_cfg['infoPage']; ?>" />					
						</td>
					</tr>
				<tbody>
				</tbody>
				</table>


			<p>Enter number of days to keep posts marked as new within Whipps app</p>
			
				<table class="widefat" width="80%" cellpadding="0" cellspacing="2" border="0">
				<thead>
		        <tr>
		        	<th class="cat-id" scope="col">New posts</th>
		        </tr>
				</thead>
					<tr>
						<td class="cat-id">
							<label for="days">Keep posts new for</label>&nbsp;
							<input type="text" size="1" name="whipps[days]" id="days" value="<?php echo $whipps_cfg['days']; ?>" /> days
						</td>
					</tr>
				<tbody>
				</tbody>
				</table>
			
			<p>Set the checkbox to exclude the respective category from the Whipps iPhone app</p>
			
				<table class="widefat" width="80%" cellpadding="0" cellspacing="2" border="0">
				<thead>
		        <tr>
		        	<th class="cat-id" scope="col"><?php _e('ID') ?></th>
		        	<th class="cat-name" scope="col"><?php _e('Category Name') ?></th>
		        	<th class="cat-action" scope="col"><?php _e('Exclude from Whipps app') ?></th>
		        </tr>
				</thead>
				<tbody>
				<?php
					foreach($categories as $cat_info)
					{	
						$class = ('alternate' == $class) ? '' : 'alternate';
						whipps_show_cat_item_row($cat_info, $class);
					}
				?>		
				</tbody>
				</table>

			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
	<?php	
}

function get_cat_parent_tree_array($cat_id=0, $level=0)
{
	$cat_info = get_category($cat_id);
	
	$parent_array = array();
	$parent_array[$level] = $cat_info;

	if (intval($cat_info->parent) > 0)
	{
		$cat_array_tmp = get_cat_parent_tree_array($cat_info->parent, $level+1);
		if ($cat_array_tmp)
			$parent_array = array_merge($parent_array, $cat_array_tmp);
	}
	return $parent_array;
}
	
function whipps_show_cat_item_row($cat_info, $class)
{
	$cat_parents = get_cat_parent_tree_array($cat_info->cat_ID, 0);
	$level_spacer = "";
	foreach($cat_parents as $cat_parent)
	{
		if ($cat_parent->cat_ID == $cat_info->cat_ID)
			continue;
			
		$level_spacer .= "&ndash;";
	}
	
	?>
	<tr <?php if (strlen($class)) echo "class='".$class."'" ?>>
		<td class="cat-id"><?php echo $cat_info->cat_ID ?></td>
		<td class="cat-name"><?php echo $level_spacer . $cat_info->cat_name ?></td>
		<td class="cat-action"><?php whipps_display_cat_action_row($cat_info->cat_ID) ?></td>
	</tr>
	<?php
}

function get_whipps_options() {
	$tmp_whipps_cfg = get_option('whipps');
	if ($tmp_whipps_cfg)
	{
		if (is_serialized($tmp_whipps_cfg))
			$whipps_cfg = unserialize($tmp_whipps_cfg);
		else
			$whipps_cfg = $tmp_whipps_cfg;
	}
	
	return $whipps_cfg;
}

function whipps_display_cat_action_row($cat_id)
{
	$whipps_cfg = get_whipps_options();
		?>
		<label for="cats-<?php echo $cat_id ?>">
			Excluded</label>&nbsp;
		<input type="checkbox" 
			name="whipps[cats][<?php echo $cat_id ?>]"
			id="cats-<?php echo $cat_id ?>"
			<?php
			if ($whipps_cfg['cats'][$cat_id] == "on")
				echo "checked='checked' ";
			?> />
		<?php
}

?>