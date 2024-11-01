<?php
/*
Plugin Name: Source View 
Plugin URI: http://ounziw.com/2012/04/27/source-view-plugin/
Description: This plugin outputs a source code of the function/class you specified.
Author: Fumito MIZUNO 
Version: 1.1
License: GPL ver.2 or later
Author URI: https://php4wordpress.calculator.jp/
 */
define( 'SV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if( is_admin() ) {
	require_once(SV_PLUGIN_DIR . '/sourceviewclass.php');
}

function sv_plugin_admin_page() {
	// Use of $hook is explained below
	// http://justintadlock.com/archives/2011/07/12/how-to-load-javascript-in-the-wordpress-admin
	// Thanks, Justin.
	$hook = add_options_page( 'Source View Options', __('Source View','source-view'), 'manage_options', 'source-view', 'sv_plugin_options' );
}
add_action( 'admin_menu', 'sv_plugin_admin_page' );

function sv_settings_api_init() {
	add_settings_section('sv_setting_section',
		__('Class/Function Source View','source-view'),
		'sv_setting_section_callback_function',
		'source-view');

	add_settings_field('sv_function_name',
		__('Class/Function Name','source-view'),
		'sv_setting_callback_function',
		'source-view',
		'sv_setting_section');

	register_setting('source-view-group','sv_function_name', 'wp_filter_nohtml_kses');
}
add_action('admin_init', 'sv_settings_api_init');

function sv_setting_section_callback_function() {
	echo '<p>'. esc_html(__('Enter a classname or function name or shortcode name.','source-view')) . '</p>';
}

function sv_setting_callback_function() {
    echo '<input name="sv_function_name" id="sv_function_name" type="text" value="';
    form_option('sv_function_name');
    echo '" class="code" />';
}

function sv_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
?>
	<div class="wrap">
		<form action="options.php" method="post">
<?php settings_fields('source-view-group'); ?>
<?php do_settings_sections('source-view'); ?>
<input name="Submit" type="submit" id="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
	</form>

<?php
	if ('' != get_option('sv_function_name')) {
		$func_or_class_name = get_option('sv_function_name');
		try {
			$reflect = sv_funcname_check($func_or_class_name);
			$obj = new Source_View($reflect);
			$filename = esc_html($obj->getFileName());
			$startline = intval($obj->getStartLine());
			$endline = intval($obj->getEndLine());
			// HTML before source code
			$before_code_format = '<p>File: %s  Line: %d - %d</p>';
			$before_code_format = apply_filters('sv_before_code_format',$before_code_format);
			printf($before_code_format, $filename, $startline, $endline);
			// source code
			echo '<pre class="brush: php; first-line: '. $startline .';">';
			echo $obj->createFileData()->outData(TRUE);
			echo '</pre>';
		} catch (Exception $e) {
			echo esc_html($e->getMessage());
		}
	}
?></div>
<?php
}

/**
 * sv_funcname_check 
 * 
 * @param string $func_or_class_name 
 * @access public
 * @return object
 */
function sv_funcname_check($func_or_class_name){
	global $shortcode_tags;
	$reflect = NULL;
	if (function_exists($func_or_class_name)) {
		$reflect = new ReflectionFunction($func_or_class_name);
	} elseif (class_exists($func_or_class_name)) {
		$reflect = new ReflectionClass($func_or_class_name);
	} elseif (array_key_exists($func_or_class_name,$shortcode_tags)) {
		$func_name = $shortcode_tags[$func_or_class_name];
		$reflect = new ReflectionFunction($func_name);
	}

	if (is_object($reflect)) {
		return $reflect;
	} else {
		throw new Exception(__('Not Found function/class','source-view'));
	}
}
