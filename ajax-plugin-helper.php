<?php
/**
 * An Ajax Plugin Helper for the WordPress admin plugin page.  Adds Ajax activate, deactivate, delete and upgrade.
 *
 * Allows a user to activate, deactive, delete and upgrade plugins from the admin plugins page without leaving the
 * plugins page.
 *
 * @author Matt Martz <matt@sivel.net>
 * @version 1.0.5
 * @package shadowbox-js
 */
/*
Plugin Name: Ajax Plugin Helper
Plugin URI: http://sivel.net/wordpress/ajax-plugin-helper/
Description: An Ajax Plugin Helper for the WordPress admin plugin page.  Adds Ajax activate, deactivate, delete and upgrade.
Author: Matt Martz
Author URI: http://sivel.net
Version: 1.0.5

		Copyright (c) 2009 Matt Martz (http://sivel.net)
		Ajax Plugin Helper is released under the GNU General Public License (GPL)
		http://www.gnu.org/licenses/gpl-2.0.txt
*/

class AjaxPluginHelper {

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @since 1.0
	 * @return none
	 */
	function AjaxPluginHelper() {
		$this->__construct();
	}

	/**
	 * PHP5 style contructor
	 *
	 * Hooks into all of the necessary WordPress actions and filters needed
	 * for this plugin to function
	 *
	 * @since 1.0
	 * @return none
	 */
	function __construct() {
		add_action('wp_ajax_ajaxpluginupdate', array(&$this, 'update'));
		add_action('wp_ajax_ajaxpluginactivate', array(&$this, 'activate'));
		add_action('wp_ajax_ajaxplugindeactivate', array(&$this, 'deactivate'));
		add_action('wp_ajax_ajaxpluginsubsubsub', array(&$this, 'subsubsub'));
		add_action('wp_ajax_ajaxplugincounts', array(&$this, 'counts'));
		add_action('wp_ajax_ajaxplugindelete', array(&$this, 'delete'));
		add_action('admin_menu', array(&$this, 'add_options_page')) ;
		add_action('admin_header-plugins.php', array(&$this, 'admin_jquery'), 7);
		add_action('admin_footer-plugins.php', array(&$this, 'admin_js'));
		register_activation_hook(__FILE__, array(&$this, 'activation'));
		add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
		add_action("in_plugin_update_message-" . plugin_basename(__FILE__), array(&$this, 'changelog'));
	}

	/**
	 * Action hook callback for activation
	 *
	 * Initializes the plugin for first time use and notifies users of 
	 * any issues they may encounter.
	 *
	 * @since 1.0
	 * @return none
	 */
	function activation() {
		if ( !$this->can_modify_fs() ) {
			set_transient('ajax-plugin-helper-fs', true, 1);
		}
	}

	/**
	 * Check if there are any messages to be displayed to the user
	 *
	 * @since 1.0
	 * @return none
	 */
	function plugins_loaded() {
		if ( ! current_user_can('update_plugins') )
			return;
		if ( get_transient('ajax-plugin-helper-fs') == true ) {
			add_action('admin_notices', array(&$this, 'modify_fs_notice'));
		}
		load_plugin_textdomain('ajax-plugin-helper', false, plugin_basename(dirname(__FILE__)) . '/localization');
	}

	/**
	 * Action hook callback for displaying message letting
	 * user know that ajax upgrade and delete functions 
	 * are not available due to not being able to perform 
	 * file system tasks without prompting for FTP/SSH/SFTP
	 * credentials
	 *
	 * @sinec 1.0
	 * @return none
	 */
	function modify_fs_notice() {
?>
	<div class="error"><?php _e('Ajax Plugin Helper has determined that it cannot provide access to the Ajax Upgrade and Ajax Delete functionality because your install cannot perform file system level tasks without prompting for FTP/SFTP connection information. Please consult the FAQ at ', 'ajax-plugin-helper'); ?><a href="http://sivel.net/wordpress/ajax-plugin-helper/#faq">http://sivel.net/wordpress/ajax-plugin-helper/#faq</a></div>
<?php		
	}

	/**
	 * Returns a JSON representation of a value
	 *
	 * Uses the JSON class included with tinymce if json_encode is not present
	 *
	 * @since 1.0
	 * @param mixed $value value to retrieve JSON representation of
	 * @return string JSON representation of value
	 */
	function json_encode($value) {
		if ( function_exists('json_encode') ) {
			return json_encode($value);
		} else {
			include(ABSPATH . WPINC . '/js/tinymce/plugins/spellchecker/classes/utils/JSON.php');
			$json = new Moxiecode_JSON();
			return $json->encode($value);
		}
	}

	/**
	 * Action hook callback for API to update the status links at the top of the 
	 * admin plugins.php page.
	 *
	 * Code taken from http://core.trac.wordpress.org/browser/tags/2.8.2/wp-admin/plugins.php
	 *
	 * @since 1.0
	 * @see http://core.trac.wordpress.org/browser/tags/2.8.2/wp-admin/plugins.php
	 * @return none
	 */
	function subsubsub() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$default_status = 'all';
			$status = isset($_REQUEST['plugin_status']) ? $_REQUEST['plugin_status'] : $default_status;
			if ( !in_array($status, array('all', 'active', 'inactive', 'recent', 'upgrade', 'search')) )
				$status = 'all';

			$all_plugins = get_plugins();
			$search_plugins = array();
			$active_plugins = array();
			$inactive_plugins = array();
			$recent_plugins = array();
			$recently_activated = get_option('recently_activated', array());
			$upgrade_plugins = array();

			set_transient( 'plugin_slugs', array_keys($all_plugins), 86400 );

			// Clean out any plugins which were deactivated over a week ago.
			foreach ( $recently_activated as $key => $time )
				if ( $time + (7*24*60*60) < time() ) //1 week
					unset($recently_activated[ $key ]);
			if ( $recently_activated != get_option('recently_activated') ) //If array changed, update it.
				update_option('recently_activated', $recently_activated);
			$current = get_transient( 'update_plugins' );

			foreach ( (array)$all_plugins as $plugin_file => $plugin_data) {

				//Translate, Apply Markup, Sanitize HTML
				$plugin_data = _get_plugin_data_markup_translate($plugin_file, $plugin_data, false, true);
				$all_plugins[ $plugin_file ] = $plugin_data;

				//Filter into individual sections
				if ( is_plugin_active($plugin_file) ) {
					$active_plugins[ $plugin_file ] = $plugin_data;
				} else {
					if ( isset( $recently_activated[ $plugin_file ] ) ) // Was the plugin recently activated?
						$recent_plugins[ $plugin_file ] = $plugin_data;
					$inactive_plugins[ $plugin_file ] = $plugin_data;
				}

				if ( isset( $current->response[ $plugin_file ] ) )
					$upgrade_plugins[ $plugin_file ] = $plugin_data;
			}

			$total_all_plugins = count($all_plugins);
			$total_inactive_plugins = count($inactive_plugins);
			$total_active_plugins = count($active_plugins);
			$total_recent_plugins = count($recent_plugins);
			$total_upgrade_plugins = count($upgrade_plugins);

			$status_links = array();
			$class = ( 'all' == $status ) ? ' class="current"' : '';
			$status_links[] = "<li><a href='plugins.php?plugin_status=all' $class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_all_plugins, 'plugins', 'ajax-plugin-helper' ), number_format_i18n( $total_all_plugins ) ) . '</a>';
			if ( ! empty($active_plugins) ) {
				$class = ( 'active' == $status ) ? ' class="current"' : '';
				$status_links[] = "<li><a href='plugins.php?plugin_status=active' $class>" . sprintf( _n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $total_active_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_active_plugins ) ) . '</a>';
			}
			if ( ! empty($recent_plugins) ) {
				$class = ( 'recent' == $status ) ? ' class="current"' : '';
				$status_links[] = "<li><a href='plugins.php?plugin_status=recent' $class>" . sprintf( _n( 'Recently Active <span class="count">(%s)</span>', 'Recently Active <span class="count">(%s)</span>', $total_recent_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_recent_plugins ) ) . '</a>';
			}
			if ( ! empty($inactive_plugins) ) {
				$class = ( 'inactive' == $status ) ? ' class="current"' : '';
				$status_links[] = "<li><a href='plugins.php?plugin_status=inactive' $class>" . sprintf( _n( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $total_inactive_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_inactive_plugins ) ) . '</a>';
			}
			if ( ! empty($upgrade_plugins) ) {
				$class = ( 'upgrade' == $status ) ? ' class="current"' : '';
				$status_links[] = "<li><a href='plugins.php?plugin_status=upgrade' $class>" . sprintf( _n( 'Upgrade Available <span class="count">(%s)</span>', 'Upgrade Available <span class="count">(%s)</span>', $total_upgrade_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_upgrade_plugins ) ) . '</a>';
			}
			if ( ! empty($search_plugins) ) {
				$class = ( 'search' == $status ) ? ' class="current"' : '';
				$term = isset($_REQUEST['s']) ? urlencode(stripslashes($_REQUEST['s'])) : '';
				$status_links[] = "<li><a href='plugins.php?s=$term' $class>" . sprintf( _n( 'Search Results <span class="count">(%s)</span>', 'Search Results <span class="count">(%s)</span>', $total_search_plugins, 'ajax-plugin-helper' ), number_format_i18n( $total_search_plugins ) ) . '</a>';
			}
			echo implode( " |</li>\n", $status_links ) . '</li>';
			unset( $status_links );
		}
		die();
	}

	/**
	 * Action hook callback for API to deactivate a plugin
	 *
	 * @since 1.0
	 * @return none
	 */
	function deactivate() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$active_plugins = get_option('active_plugins');
			if ( in_array($_GET['plugin'], $active_plugins) ) {
				deactivate_plugins($_GET['plugin']);
			}
			if ( ! is_plugin_active($_GET['plugin']) ) {
				echo $this->json_encode(array('response' => 0, 'plugin' => $_GET['plugin']));
			} else {
				echo $this->json_encode(array('response' => 1, 'plugin' => $_GET['plugin']));
			}
		}
		die();
	}

	/**
	 * Action hook callback for API to activate a plugin.
	 *
	 * @since 1.0
	 * @return none
	 */
	function activate() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$active_plugins = get_option('active_plugins');
			if ( ! in_array($_GET['plugin'], $active_plugins) ) {
				// Output buffer to supress errors on activation
				ob_start();
				activate_plugin($_GET['plugin']);
				ob_end_clean();
			}
			if ( ! is_plugin_active($_GET['plugin']) ) {
				echo $this->json_encode(array('response' => 1, 'plugin' => $_GET['plugin']));
			} else {
				echo $this->json_encode(array('response' => 0, 'plugin' => $_GET['plugin']));
			}
		}
		die();
	}

	/**
	 * Action hook callback for API to delete a plugin.
	 *
	 * @since 1.0
	 * @return none
	 */
	function delete() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce'])) {
			if ( ! is_plugin_active($_GET['plugin']) ) {
				$delete_result = delete_plugins(array($_GET['plugin']));
				if ( $delete_result === true ) {
					echo $this->json_encode(array('response' => 0, 'plugin' => $_GET['plugin']));
				} else {
					echo $this->json_encode(array('response' => 1, 'plugin' => $_GET['plugin']));
				}
			}
		}
		die();
	}

	/**
	 * Action hook callback for API to update a plugin.
	 *
	 * @since 1.0
	 * @return none
	 */
	function update() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce'])) {
			$active_plugins = get_option('active_plugins');
			// Output buffer the update to suppress the echoes it generates
			ob_start();
			wp_update_plugin($_GET['plugin']);
			$output = ob_get_contents();
			ob_end_clean();
			// Remove tags from the output that we don't want
			echo preg_replace('%</?(div|h2|br)([^>]+)?>%i', '', $output);
			// Check if the was active before the update and that the update did not fail.
			if ( in_array($_GET['plugin'], $active_plugins) && ! stristr($output, 'Failed') ) {
				echo '<p>Attempting plugin reactivation.</p>' . "\n";
				echo '<p class="' . str_replace(array('/','.'), '-', $_GET['plugin']) . '-autoactivate"><img src="' . admin_url('images/wpspin_light.gif') . '" alt="' . __('Loading...', 'ajax-plugin-helper') . '" /></p>' . "\n";
?>
<script type="text/javascript">
/* <![CDATA[ */
	var ajaxpluginautoactivate = function(data) {
		var baseclass = '.' + data.plugin.replace('/','-').replace('.','-');
		if (data.response == 0) {
			var message = 'Plugin reactivated successfully.';
		} else {
			var message = 'Plugin could not be reactivated successfully.';
		}
		jQuery(baseclass + '-autoactivate').html(message);
	}
	jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: "ajaxpluginactivate", plugin: "<?php echo $_GET['plugin']; ?>", _wpnonce: "<?php echo wp_create_nonce(); ?>"}, ajaxpluginautoactivate, "json");
/* ]]> */
</script>
<?php
			} else {
				echo '<p>Plugin reactivation not attempted.</p>';
			}
?>
<script type="text/javascript">
/* <![CDATA[ */
	jQuery('.<?php echo str_replace(array('/','.'), '-', $_GET['plugin']); ?>-update').parent().remove();
	reloadcounts();
/* ]]> */
</script>
<?php
		}
		die();
	}

	/**
	 * Action hook callback for API to retrieve the number of plugins requiring updates.
	 *
	 * This function will echo the number of plugins requiring updates.
	 *
	 * @since 1.0
	 * @return none
	 */
	function counts() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			wp_update_plugins();
			$update_plugins = get_transient('update_plugins');
			$update_count = 0;
			if ( !empty($update_plugins->response) )
				$update_count = count($update_plugins->response);
			echo $update_count;
		}
		die();		
	}

	/**
	 * Action hook callback to filter the plugin action links
	 *
	 * @since 1.0
	 * @return none
	 */
	function add_options_page() {
		if ( current_user_can('update_plugins') ) {
			add_filter("plugin_action_links" ,array(&$this, 'filter_plugin_actions'), 10, 2);
		}
	}

	/**
	 * Function to check whether PHP can make file system level changes
	 * without requesting login information at the time the update or 
	 * deletion is requested.
	 *
	 * @since 1.0
	 * @see http://codex.wordpress.org/Editing_wp-config.php#FTP.2FSSH_Constants
	 * @see http://www.firesidemedia.net/dev/wordpress-install-upgrade-ssh/
	 * @return boolean
	 */
	function can_modify_fs() {
		// Output buffer to supress the echoes from request_filesystem_credentials
		ob_start();
		if ( false !== ($credentials = request_filesystem_credentials('')) ) {
			ob_end_clean();
			return true;
		} else {
			ob_end_clean();
			return false;
		}
	}

	/**
	 * Action hook callback to populate update message show in below each plugin
	 * requiring an update on plugins.php
	 *
	 * Code taken from http://core.trac.wordpress.org/browser/tags/2.8.2/wp-admin/update.php
	 *
	 * @since 1.0
	 * @see http://core.trac.wordpress.org/browser/tags/2.8.2/wp-admin/update.php
	 * @param string $file plugin_basename of the current plugin the action was called for
	 * @param array $plugin_data array of plugin information of the current plugin the action was called for
	 * @return none
	 */
	function wp_plugin_update_row($file, $plugin_data) {
		$current = get_transient('update_plugins');
		if ( !isset($current->response[ $file ]) )
			return false;

		$r = $current->response[ $file ];

		$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
		$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );

		$details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=' . $r->slug . '&TB_iframe=true&width=600&height=800');

		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">';
		if ( ! current_user_can('update_plugins') )
			printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a>.', 'ajax-plugin-helper'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version );
		else if ( empty($r->package) )
			printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a> <em>automatic upgrade unavailable for this plugin</em>.', 'ajax-plugin-helper'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version );
		else if ( ! $this->can_modify_fs() || $file == plugin_basename(__FILE__) ) 
			printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a> or <a href="%5$s">upgrade automatically</a>.', 'ajax-plugin-helper'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version, wp_nonce_url('update.php?action=upgrade-plugin&plugin=' . $file, 'upgrade-plugin_' . $file) );
		else
			printf( __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a> or <a class="ajaxpluginupdate %5$s-update" rel="%6$s" href="">Ajax Upgrade</a>.', 'ajax-plugin-helper'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version, str_replace(array('/','.'), '-', $file), $file );

		do_action( "in_plugin_update_message-$file", $plugin_data, $r );

		echo '</div></td></tr>';
	}

	/**
	 * Filter hook callback to insert and modify plugin action links
	 *
	 * @since 1.0
	 * @param array $links array of current action links to be filtered
	 * @param string $file plugin_basename of the current plugin the filter was called for
	 * @return array array of plugin action links
	 */
	function filter_plugin_actions($links, $file) {
		global $wp_version;
		// Remove the core update message action callback and use a custom one
		if ( $file != plugin_basename(__FILE__) ) {
			if ( version_compare('2.9', preg_replace('/[a-z-]+/i', '', $wp_version), '<=') ) {
				remove_action("after_plugin_row_$file", 'wp_plugin_update_row', 10, 2);
				add_action("after_plugin_row_$file", array(&$this, 'wp_plugin_update_row'), 10, 2);
			} else {
				remove_action('after_plugin_row', 'wp_plugin_update_row', 10, 2);
				add_action('after_plugin_row', array(&$this, 'wp_plugin_update_row'), 10, 2);
			}

		}

		$update_plugins = get_transient('update_plugins');
		if ( current_user_can('update_plugins') && $file != plugin_basename(__FILE__) ) {
			$class = str_replace(array('/','.'), '-', $file);
			$spin_act = "<img class='{$class}-spin hidden' src='" . admin_url('images/wpspin_light.gif') . "' alt='" . __('Loading...', 'ajax-plugin-helper') . "' />";
			$spin_del = "<img class='{$class}-spin-del hidden' src='" . admin_url('images/wpspin_light.gif') . "' alt='" . __('Loading...', 'ajax-plugin-helper') . "' />";
			// If plugin requires updating and php can make unquestioned file system changes add update link
			if ( isset($update_plugins->response[$file]) && $this->can_modify_fs() ) {
				array_unshift($links, "<a class='ajaxpluginupdate {$class}-update' rel='{$file}' href=''>" . __('Ajax Upgrade', 'ajax-plugin-helper') . '</a>');
			}
			for ( $i = 0; $i < count($links); $i++ ) {
				if ( strstr($links[$i], 'action=activate') ) { // Replace activate link with new
					unset($links[$i]);
					array_unshift($links, "{$spin_act}<a class='ajaxpluginactivate {$class}-activate' rel='{$file}' href=''>" . __('Ajax Activate', 'ajax-plugin-helper') . "</a><a class='ajaxplugindeactivate {$class}-deactivate hidden' rel='{$file}' href=''>" . __('Ajax Deactivate', 'ajax-plugin-helper') . '</a>');
				} else if ( strstr($links[$i], 'action=deactivate') ) { // Replace deactivate link with new
					unset($links[$i]);
					array_unshift($links, "{$spin_act}<a class='ajaxplugindeactivate {$class}-deactivate' rel='{$file}' href=''>" . __('Ajax Deactivate', 'ajax-plugin-helper') . "</a><a class='ajaxpluginactivate {$class}-activate hidden' rel='{$file}' href=''>" . __('Ajax Activate', 'ajax-plugin-helper') . '</a>');
				} else if ( strstr($links[$i], 'action=delete-selected' ) && $this->can_modify_fs() ) { // Modify delete link if exists and can modify fs
					$links[$i] = "{$spin_del}<a class='ajaxplugindelete {$class}-delete' rel='{$file}' href=''>" . __('Ajax Delete', 'ajax-plugin-helper') . "</a>";
					$delete_exists = true;
				}
			}
			if ( !isset($delete_exists) ) {
				$links[$i] = "{$spin_del}<a class='ajaxplugindelete {$class}-delete hidden' rel='{$file}' href=''>" . __('Ajax Delete', 'ajax-plugin-helper') . "</a>";
			}
		}
		return $links;
	}

	/**
	 * Retrieves a changelog and outputs it into the upgrade notice
	 *
	 * @since 1.0
	 * @return none
	 */
	function changelog () {
		$url = "http://plugins.svn.wordpress.org/ajax-plugin-helper/trunk/upgrade.html";
		$response = wp_remote_get($url);
		$code = (int) wp_remote_retrieve_response_code($response);
		if ( $code == 200 ) {
			$body = wp_remote_retrieve_body($response);
			echo "\n<p class='upgrade'>\n$body\n</p>\n";
		}
	}

	/**
	 * Enqueue jQuery so this plugin can use it
	 *
	 * @since 1.0
	 * @return none
	 */
	function admin_jquery() {
		wp_enqueue_script('jquery');
	}

	/**
	 * Echo the JS required for this plugin to work
	 *
	 * @since 1.0
	 * @return none
	 */
	function admin_js() {
?>
<script type="text/javascript">
/* <![CDATA[ */
	(function($) {
		$.fn.quadParent = function() {
			return this.parent().parent().parent().parent();
		}
	})(jQuery);
	var reloadcounts = function() {
		jQuery('.subsubsub').load('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginsubsubsub&_wpnonce=<?php echo wp_create_nonce(); ?>&plugin_status=' + jQuery('input[name="plugin_status"]').val());
		jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxplugincounts', _wpnonce: '<?php echo wp_create_nonce(); ?>'}, function(count) {
			updateplugins = jQuery('.update-plugins');
			jQuery(updateplugins).removeClass();
			jQuery(updateplugins).addClass('update-plugins count-' + count);
			jQuery('.plugin-count').html(count);
		}, 'text');
	}
	var ajaxpluginactivate = function(data) {
		var baseclass = '.' + data.plugin.replace('/','-').replace('.','-');
		jQuery(baseclass + '-spin').addClass('hidden');
		if (data.response == 0) {
			jQuery(baseclass + '-deactivate').removeClass('hidden');
			jQuery(baseclass + '-deactivate').quadParent().removeClass('inactive');
			jQuery(baseclass + '-deactivate').quadParent().addClass('active');
			jQuery(baseclass + '-deactivate').quadParent().prev().removeClass('inactive');
			jQuery(baseclass + '-deactivate').quadParent().prev().addClass('active');
			jQuery(baseclass + '-delete').addClass('hidden');
			jQuery(baseclass + '-delete').parent().prev().html(jQuery(baseclass + '-delete').parent().prev().html().replace('|',''));
			reloadcounts();
		} else {
			jQuery(baseclass + '-activate').removeClass('hidden');
		}
	}
	var ajaxplugindeactivate = function(data) {
		var baseclass = '.' + data.plugin.replace('/','-').replace('.','-');
		jQuery(baseclass + '-spin').addClass('hidden');
		if (data.response == 0) {
			jQuery(baseclass + '-activate').removeClass('hidden');
			jQuery(baseclass + '-activate').quadParent().removeClass('active');
			jQuery(baseclass + '-activate').quadParent().addClass('inactive');
			jQuery(baseclass + '-activate').quadParent().prev().removeClass('active');
			jQuery(baseclass + '-activate').quadParent().prev().addClass('inactive');
			jQuery(baseclass + '-delete').parent().prev().html(jQuery(baseclass + '-delete').parent().prev().html() + ' | ');
			jQuery(baseclass + '-delete').removeClass('hidden');
			reloadcounts();
		} else {
			jQuery(baseclass + '-deactivate').removeClass('hidden');
		}
	}
	var ajaxplugindelete = function(data) {
		var baseclass = '.' + data.plugin.replace('/','-').replace('.','-');
		jQuery(baseclass + '-spin-del').addClass('hidden');
		if (data.response == 0) {
			jQuery(baseclass + '-delete').quadParent().prev().remove();
			if (jQuery(baseclass + '-delete').quadParent().next().next().attr('class') == 'plugin-update-tr') {
				jQuery(baseclass + '-delete').quadParent().next().next().remove();
				jQuery(baseclass + '-delete').quadParent().next().remove();
			} else if (jQuery(baseclass + '-delete').quadParent().next().attr('class') == 'plugin-update-tr') {
				jQuery(baseclass + '-delete').quadParent().next().remove();
			} else if (jQuery(baseclass + '-delete').quadParent().next().attr('class').length == 0) {
				jQuery(baseclass + '-delete').quadParent().next().remove();
			}
			jQuery(baseclass + '-delete').quadParent().remove();
			reloadcounts();
		} else {
			jQuery(baseclass + '-delete').removeClass('hidden');
		}
	}
	jQuery(document).ready(function() {
<?php
		$update_plugins = get_transient( 'update_plugins' );
		$update_count = 0;
		if ( !empty($update_plugins->response) )
			$update_count = count( $update_plugins->response );
		if ( $update_count > 0 ) :
?>
		jQuery('.actions').append('<input type="submit" value="<?php _e('Ajax Upgrade All', 'ajax-plugin-helper'); ?>" class="button-secondary ajaxpluginupdateall" />');
<?php endif; ?>
		jQuery('.ajaxplugindelete:hidden').each(function() {
			if (jQuery(this).parent().prev().html() != null) {
				jQuery(this).parent().prev().html(jQuery(this).parent().prev().html().replace('|',''));
			}
		});
		jQuery('.ajaxpluginupdateall').click(function() {
			jQuery('.ajaxpluginupdate').each(function() {
				if (jQuery(this).quadParent().next().children().children().attr('class') == 'update-message' ) {
					updatetr = jQuery(this).quadParent().next().children().children();
				} else {
					updatetr = jQuery(this).quadParent().next().next().children().children();
				}
				jQuery(updatetr).html('<img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" alt="<?php _e('Loading...', 'ajax-plugin-helper'); ?>" />');
				jQuery(updatetr).load('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginupdate&plugin=' + jQuery(this).attr('rel') + '&_wpnonce=<?php echo wp_create_nonce(); ?>');
				jQuery('.ajaxpluginupdateall').remove();
			});
			return false;
		});
		jQuery('.ajaxpluginupdate').click(function() {
			if (jQuery(this).quadParent().next().children().children().attr('class') == 'update-message' ) {
				updatetr = jQuery(this).quadParent().next().children().children();
			} else if (jQuery(this).quadParent().next().next().children().children().attr('class') == 'update-message' ) {
				updatetr = jQuery(this).quadParent().next().next().children().children();
			} else {
				updatetr = jQuery(this).parent();
			}
			jQuery(updatetr).html('<img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" alt="<?php _e('Loading...', 'ajax-plugin-helper'); ?>" />');
			jQuery(updatetr).load('<?php echo admin_url('admin-ajax.php'); ?>', 'action=ajaxpluginupdate&plugin=' + jQuery(this).attr('rel') + '&_wpnonce=<?php echo wp_create_nonce(); ?>');
			return false;
		});
		jQuery('.ajaxpluginactivate').click(function() {
			var baseclass = '.' + jQuery(this).attr('rel').replace('/','-').replace('.','-');
			jQuery(baseclass + '-activate').addClass('hidden');
			jQuery(baseclass + '-spin').removeClass('hidden');
			jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxpluginactivate', plugin: jQuery(this).attr('rel'), _wpnonce: '<?php echo wp_create_nonce(); ?>'}, ajaxpluginactivate, 'json');
			return false;
		});
		jQuery('.ajaxplugindeactivate').click(function() {
			var baseclass = '.' + jQuery(this).attr('rel').replace('/','-').replace('.','-');
			jQuery(baseclass + '-deactivate').addClass('hidden');
			jQuery(baseclass + '-spin').removeClass('hidden');
			jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxplugindeactivate', plugin: jQuery(this).attr('rel'), _wpnonce: '<?php echo wp_create_nonce(); ?>'}, ajaxplugindeactivate, 'json');
			return false;
		});
		jQuery('.ajaxplugindelete').click(function() {
			var baseclass = '.' + jQuery(this).attr('rel').replace('/','-').replace('.','-');
			jQuery(baseclass + '-delete').addClass('hidden');
			jQuery(baseclass + '-spin-del').removeClass('hidden');
			jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'ajaxplugindelete', plugin: jQuery(this).attr('rel'), _wpnonce: '<?php echo wp_create_nonce(); ?>'}, ajaxplugindelete, 'json');
			return false
		})
	});
/* ]]> */
</script>
<?php
	}

}

// Only if we are in the admin load up this plugin
if ( is_admin() ) {
	$AjaxPluginHelper = new AjaxPluginHelper();
}
