<?php

/**
 * Plugin Name:  Admin Posts Manager
 * Plugin URI:   http://wordpress.org/plugins/admin-posts-manager/
 * Description:  An advanced/beautiful way for managing your posts in the WordPress admin.
 * Version:      0.0.2
 * Author:       TheConcept
 * Author URI:   https://profiles.wordpress.org/theconcept/
 * License:      GPL-3.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:  wp-apm
 * Domain Path:  /languages
 *
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// check if php version is greater or equal to 7.0
if (version_compare(PHP_VERSION, '7.0', '<')) {
	add_action(
		'admin_notices',
		function () {
?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					'%1s requires PHP version 7.0 or higher. Please update your PHP version or contact your hosting provider.',
					'<b>Admin Posts Manager</b>'
				)
				?>
			</p>
		</div>
<?php
		}
	);
	return;
}

define('WP_APM_VERSION', '0.0.2');
define('WP_APM_MIN_PHP', '7.0');
define('WP_APM_MIN_WP', '5.0');
define('WP_APM_FILE', __FILE__);
define('WP_APM_BASE', plugin_basename(WP_APM_FILE));
define('WP_APM_PATH', plugin_dir_path(WP_APM_FILE));
define('WP_APM_URL', plugin_dir_url(WP_APM_FILE));
define('WP_APM_UPLOAD_DIR', wp_upload_dir());


register_activation_hook(__FILE__, 'apm_plugin_activate');


function apm_plugin_activate()
{
	set_transient('apm_plugin_activated', true, 48 * HOUR_IN_SECONDS);
}

require_once WP_APM_PATH . 'vendor/autoload.php';
require_once WP_APM_PATH . 'includes/init.php';
