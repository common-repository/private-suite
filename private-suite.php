<?php
/*
Plugin Name: Private Suite
Plugin URI: http://sillybean.net/code/wordpress/private-suite/
Description: Allows admins to add private pages to <code>wp_list_pages()</code>, <code>wp_page_menu()</code>, and the Pages widget or use a separate <code>wp_list_private_pages()</code> tag; specify the prefix on private and password-protected titles; create private categories; and choose which user roles may read private pages and posts.
Version: 2.1
Author: Stephanie Leary
Author URI: http://sillybean.net/
License: GPL2
*/

register_activation_hook( __FILE__, 'private_suite_activate' );
function private_suite_activate() { 
	 $options = private_suite_get_options();
	 add_option('private_suite', $options, '', 'yes');
}

add_action('admin_menu', 'private_suite_add_options_page');

// defaults
function private_suite_get_options() {
	$defaults = array(
		'private_title_prefix' => __("Private: "),
		'protected_title_prefix' => __("Protected: "), 
		'private_suite_filter' => 0,
		'categories' => 0
	);
	$options = get_option('private_suite');	
	if (!is_array($options)) $options = array();
	return array_merge($defaults, $options);
}

// add our options page
function private_suite_add_options_page() {
    // Add a new submenu under Options:
	$pg = add_options_page(__('Private Suite', 'private-suite'), __('Private Suite', 'private-suite'), 'manage_options', basename(__FILE__), 'private_suite_options_page');
	add_action("admin_head-$pg", 'private_suite_options_css');
	$options = private_suite_get_options();
	add_option('private_suite', $options, '', 'yes');
	// register setting
	add_action( 'admin_init', 'register_private_suite_settings' );
}

//register our settings
function register_private_suite_settings() {
	register_setting( 'private_suite', 'private_suite');
}

// Add link to options from plugin list
function private_suite_plugin_actions($links, $file) {
	if ($file == 'private-suite/private-suite.php' && function_exists("admin_url")) {
 		$settings_link = '<a href="' . admin_url('options-general.php?page=private-suite') . '">' . __('Settings', 'private-suite') . '</a>';
		array_unshift($links, $settings_link); 
	}
	return $links;
}
add_filter('plugin_action_links', 'private_suite_plugin_actions', 10, 2);

register_uninstall_hook( __FILE__, 'private_suite_remove_plugin' );
function private_suite_remove_plugin() {
	delete_option('private_suite');
}

/*------------------------ Set Prefixes ------------------------*/
add_filter('private_title_format', 'private_suite_custom_private_title');

function private_suite_custom_private_title() {
	$options = private_suite_get_options();
	return $options['private_title_prefix'];
}

add_filter('protected_title_format', 'private_suite_custom_protected_title');

function private_suite_custom_protected_title() {
	$options = private_suite_get_options();
	return $options['protected_title_prefix'];
}

/*---------------------- Private Categories -----------------------*/
add_action('save_post', 'set_private_categories', 10, 1);

function set_private_categories($postid) {
	if ($parent_id = wp_is_post_revision($postid)) 
		$postid = $parent_id;
	$options = get_option('private_suite');
		if (in_category($options['categories'], $postid)) {
			// unhook this function so it doesn't loop infinitely, update the post, then re-hook
			remove_action('save_post', 'set_private_categories', 10, 1);
			wp_update_post(array('ID' => $postid, 'post_status' => 'private'));
			add_action('save_post', 'set_private_categories', 10, 1);
		}
}

/*-------------- Fix public loops and page lists --------------*/
add_filter('pre_get_posts', 'private_suite_query_filter');

function private_suite_query_filter($query) {
	$options = private_suite_get_options();
	if ($options['private_suite_filter'])
		$query->set('post_status', 'publish,private');
	return $query;
}

function wp_list_private_pages($args) {
	return wp_list_pages(array_merge($args, array('post_status' => 'publish,private')));
}

/*--------------------- Fix admin page lists -------------------*/
// Add private/draft/future/pending pages to page attributes meta box and quick edit
add_filter( 'page_attributes_dropdown_pages_args', 'private_suite_add_parents', 10, 2 ); 
add_filter( 'quick_edit_dropdown_pages_args', 'private_suite_add_parents', 10);

function private_suite_add_parents( $dropdown_args, $post = NULL ) {
	$dropdown_args['post_status'] = array('publish', 'draft', 'pending', 'future', 'private');
	return $dropdown_args;
}

// Add (status) to titles in page parent dropdowns
add_filter( 'list_pages', 'private_suite_parent_status_filter', 10, 2);

function private_suite_parent_status_filter( $title, $page ) {
	$status = $page->post_status;
	if ($status != __('publish'))
		$title .= " ($status)";
	return $title;
}

/*---------------------- Page Widget ---------------------------*/
// filter the regular widget
add_filter('widget_pages_args', 'private_suite_filter_pages_args');
function private_suite_filter_pages_args($r) {
	$options = private_suite_get_options();
	if ($options['private_suite_filter'])
		return array_merge($r, array('post_status' => 'publish,private'));
	else return $r;
}

class WP_Widget_Private_Pages extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_private_pages', 'description' => __( 'Your blog&#8217;s WordPress Pages, including the private ones', 'private-suite') );
		parent::__construct('private_pages', __('Pages (with private)', 'private-suite'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Pages' , 'private-suite') : $instance['title']);
		$sortby = empty( $instance['sortby'] ) ? 'menu_order' : $instance['sortby'];
		$exclude = empty( $instance['exclude'] ) ? '' : $instance['exclude'];

		if ( $sortby == 'menu_order' )
			$sortby = 'menu_order, post_title';

		$out = wp_list_pages( apply_filters('widget_pages_args', array('post_status' => 'publish,private', 'title_li' => '', 'echo' => 0, 'sort_column' => $sortby, 'exclude' => $exclude) ) );

		if ( !empty( $out ) ) {
			echo $before_widget;
			if ( $title)
				echo $before_title . $title . $after_title;
		?>
		<ul>
			<?php echo $out; ?>
		</ul>
		<?php
			echo $after_widget;
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field($new_instance['title']);
		if ( in_array( $new_instance['sortby'], array( 'post_title', 'menu_order', 'ID' ) ) ) {
			$instance['sortby'] = $new_instance['sortby'];
		} else {
			$instance['sortby'] = 'menu_order';
		}

		$instance['exclude'] = sanitize_text_field( $new_instance['exclude'] );

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'sortby' => 'post_title', 'title' => '', 'exclude' => '') );
	?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','private-suite'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" /></p>
		<p>
			<label for="<?php echo $this->get_field_id('sortby'); ?>"><?php _e( 'Sort by:','private-suite'); ?></label>
			<select name="<?php echo $this->get_field_name('sortby'); ?>" id="<?php echo $this->get_field_id('sortby'); ?>" class="widefat">
				<option value="post_title"<?php selected( $instance['sortby'], 'post_title' ); ?>><?php _e('Page title','private-suite'); ?></option>
				<option value="menu_order"<?php selected( $instance['sortby'], 'menu_order' ); ?>><?php _e('Page order','private-suite'); ?></option>
				<option value="ID"<?php selected( $instance['sortby'], 'ID' ); ?>><?php _e( 'Page ID'); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e( 'Exclude:','private-suite'); ?></label> <input type="text" value="<?php echo esc_attr( $instance['exclude'] ); ?>" name="<?php echo $this->get_field_name('exclude'); ?>" id="<?php echo $this->get_field_id('exclude'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Page IDs, separated by commas.','private-suite'); ?></small>
		</p>
<?php
	}
}

function private_suite_widgets_init() {
	register_widget('WP_Widget_Private_Pages');
}

add_action('widgets_init', 'private_suite_widgets_init');

/*------------------------ Options Forms ------------------------*/

function private_suite_options_page() { ?>    
	<div class="wrap">
	<form method="post" id="private_suite_form" action="options.php">
    <?php 
		settings_fields('private_suite');
		get_settings_errors( 'private_suite' );	
		settings_errors( 'private_suite' );
		$options = private_suite_get_options();
	?>
	
    <h2><?php _e( 'Private Suite Options', 'private-suite'); /* print_r($options); */ ?></h2>
    
    <div id="general">
        
    <p><label for="private_title_prefix"><?php _e( 'Prefix for private post and page titles:' , 'private-suite'); ?></label><br />
	<input type="text" id="private_title_prefix" name="private_suite[private_title_prefix]" value="<?php echo esc_attr($options['private_title_prefix']); ?>" /></p>
    <p><label for="protected_title_prefix"><?php _e( 'Prefix for password-protected post and page titles:' , 'private-suite'); ?></label><br />
	<input type="text" id="protected_title_prefix" name="private_suite[protected_title_prefix]" value="<?php echo esc_attr($options['protected_title_prefix']); ?>" /></p>
    
    <p><label for="private_suite_filter"><input type="checkbox" value="1" name="private_suite[private_suite_filter]" <?php checked($options['private_suite_filter']); ?> />
	<?php _e('Try to add private pages to <code>wp_list_pages()</code>, <code>wp_page_menu()</code>, and the Pages widget.', 'private-suite'); ?></label><br />
	<small><?php _e("This might not work as expected. If it doesn't, try using the <code>wp_list_private_pages()</code> template tag instead.", 'private-suite'); ?></small></p>
    </div>
    
    <div id="categorydiv">
    	<h3><?php _e('Private Categories', 'private-suite'); ?></h3>
        <p><?php _e('Posts in this category will automatically have their visibility set to Private when they&#8217;re published.', 'private-suite'); ?></p>
        <ul class="categorychecklist">
    		<?php wp_category_checklist(0, 0, $options['categories'], false, new Private_Suite_Walker_Category_Checklist, false); ?>
        </ul>
    </div>

<p id="members">
<?php 
if (class_exists('Members_Load'))
	printf(__('<a href="%s">Manage privacy capabilities using Members</a>.', 'private-suite'), admin_url('users.php?page=roles'));
else 
	printf(__('To manage which roles can read private posts and pages, please <a href="%s">install the Members plugin</a> by Justin Tadlock. It is much more complete than the options previously offered in this plugin.', 'private-suite'), admin_url('plugin-install.php?tab=search&type=term&s=members+greenshady&plugin-search-input=Search+Plugins')); 
?></p>
    
	<p class="submit">
	<input type="submit" name="submit" class="button-primary" value="<?php _e('Update Options', 'private-suite'); ?>" />
	</p>
	</form>
	</div>
<?php 
} // end function private_suite_options() 


// custom walker so we can change the name attribute of the category checkboxes (until #16437 is fixed)
// mostly a duplicate of Walker_Category_Checklist
class Private_Suite_Walker_Category_Checklist extends Walker {
     var $tree_type = 'category';
     var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); 

 	function start_lvl(&$output, $depth = 0, $args = array() ) {
         $indent = str_repeat("\t", $depth);
         $output .= "$indent<ul class='children'>\n";
     }
 
 	function end_lvl(&$output, $depth = 0, $args = array() ) {
         $indent = str_repeat("\t", $depth);
         $output .= "$indent</ul>\n";
     }
 
	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		if ( empty( $args['taxonomy'] ) ) {
		    $taxonomy = 'category';
		} else {
		    $taxonomy = $args['taxonomy'];
		}

		$name = 'private_suite[categories]';

		$args['popular_cats'] = empty( $args['popular_cats'] ) ? array() : $args['popular_cats'];
		$class = in_array( $category->term_id, $args['popular_cats'] ) ? ' class="popular-category"' : '';

		$args['selected_cats'] = empty( $args['selected_cats'] ) ? array() : $args['selected_cats'];

		if ( ! empty( $args['list_only'] ) ) {
		    $aria_cheched = 'false';
		    $inner_class = 'category';

		    if ( in_array( $category->term_id, $args['selected_cats'] ) ) {
		        $inner_class .= ' selected';
		        $aria_cheched = 'true';
		    }

		    /** This filter is documented in wp-includes/category-template.php */
		    $output .= "\n" . '<li' . $class . '>' .
		        '<div class="' . $inner_class . '" data-term-id=' . $category->term_id .
		        ' tabindex="0" role="checkbox" aria-checked="' . $aria_cheched . '">' .
		        esc_html( apply_filters( 'the_category', $category->name ) ) . '</div>';
		} else {
		    /** This filter is documented in wp-includes/category-template.php */
		    $output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" .
		        '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' .
		        checked( in_array( $category->term_id, $args['selected_cats'] ), true, false ) .
		        disabled( empty( $args['disabled'] ), false, false ) . ' /> ' .
		        esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
		}
	}
 
 	function end_el(&$output, $object, $depth = 0, $args = array() ) {
         $output .= "</li>\n";
     }
}

function private_suite_options_css() {
	echo '<style type="text/css">
		p#members { clear: both; margin-top: 2em; }
		div#general { float: left; width: 40%; margin-right: 5%; }
		div#categorydiv { float: left; width: 40%; }
		div.categorychecklistbox { float: left; margin: 1em 1em 1em 0; }
		ul.categorychecklist { height: 15em; width: 20em; overflow-y: scroll; border: 1px solid #dfdfdf; padding: 0 1em; background: #fff; border-radius: 4px; -moz-border-radius: 4px; -webkit-border-radius: 4px; }
		ul.categorychecklist ul.children { margin-left: 1em; }
		p.submit { clear: both; }
	</style>';
}

// i18n
load_plugin_textdomain( 'private-suite', '', plugin_dir_path(__FILE__) . '/languages' );