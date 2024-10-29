<?php

namespace APM\Core\Admin;

use APM\Core\Admin\Apps\Posts;

class Admin
{
	private static $instance;

	public static function init()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct()
	{
		// actions
		add_action('admin_menu', [$this, 'add_menu_pages'], 999);
		add_action('wp_loaded', [$this, 'mount_screens']);
		add_filter('admin_footer_text', [$this, 'admin_footer_text']);
		add_filter('admin_print_scripts', [$this, 'disable_admin_notices']);
	}

	public function disable_admin_notices()
	{
		$screen = get_current_screen();
		$plugin_screens = Utils::get_screens();


		if (in_array($screen->id, $plugin_screens)) {
			remove_all_actions('admin_notices');
		}

		add_action('admin_notices', [$this, 'feedback_notice']);
	}

	public function admin_footer_text($footer_text)
	{
		$screen = get_current_screen();
		$feedback_url = Utils::get_default_global_variables()['plugin_review_url'];
		$plugin_screens = Utils::get_screens();

		if (in_array($screen->id, $plugin_screens)) {
			$footer_text = sprintf(
				'Enjoyed <strong>%s</strong> Please leave us a <a href="%s" target="_blank">%s</a> rating. We really appreciate your support. Thank you!',
				__(Utils::get_plugin_name(), Utils::get_text_domain()),
				$feedback_url,
				'&#9733;&#9733;&#9733;&#9733;&#9733;',
			);
		}

		return $footer_text;
	}

	public function feedback_notice()
	{
		$screen = get_current_screen();
		$feedback_url = Utils::get_default_global_variables()['plugin_support_url'];
		$plugin_screens = Utils::get_screens();

		if (isset($_GET['page']) && $_GET['page'] === 'admin-posts-manager') {
			if (isset($_GET['apm_dismiss_feedback']) && $_GET['apm_dismiss_feedback'] == '1') {
				$wpnonce = $_GET['_wpnonce'];
				if (wp_verify_nonce($wpnonce, 'apm_dismiss_feedback')) {
					update_option('apm_dismiss_feedback', '1');
				}
			}
			if (isset($_GET['apm_maybe_later_feedback']) && $_GET['apm_maybe_later_feedback'] == '1') {
				$wpnonce = $_GET['_wpnonce'];
				if (wp_verify_nonce($wpnonce, 'apm_dismiss_feedback')) {
					set_transient('apm_maybe_later_feedback', '1', WEEK_IN_SECONDS);
				}
			}
		}

		$is_notice_dismissed = get_option('apm_dismiss_feedback');
		$is_maybe = get_transient('apm_maybe_later_feedback');
		$is_plugin_just_activated = get_transient('apm_plugin_activated');

		if (in_array($screen->id, $plugin_screens) && $is_notice_dismissed != '1' && $is_maybe != '1' && is_admin() && !$is_plugin_just_activated) {
?>
			<div class="notice notice-info updated put-dismiss-notice is-dismissible ">
				<p>
					Are you enjoying <strong><?php echo Utils::get_plugin_name() ?></strong>? Please <a href="<?php echo esc_url($feedback_url); ?>" target="_blank">give us feedback</a> to help us improve. I would love to hear your thoughts.
					<br /><strong>~ The Concept Team</strong>
				</p>
				<p>
					<a href="<?php echo esc_url($feedback_url); ?>" target="_blank" class="button button-primary">Give Feedback</a>
					<a href="<?php echo esc_html(
									wp_nonce_url(
										add_query_arg(
											[
												'apm_maybe_later_feedback' => 1,
											],
											admin_url('admin.php?page=admin-posts-manager')
										),
										'apm_dismiss_feedback'
									)
								);  ?>" class="button button-secondary put-dismiss-notice">Maybe later</a>
					<a href="<?php echo esc_html(
									wp_nonce_url(
										add_query_arg(
											[
												'apm_dismiss_feedback' => 1,
											],
											admin_url('admin.php?page=admin-posts-manager')
										),
										'apm_dismiss_feedback'
									)
								);  ?>" class="button button-secondary put-dismiss-notice">No Thanks.</a>
				</p>
			</div>
<?php
		}
	}

	public function add_menu_pages()
	{
		add_menu_page(
			__('APM UI', 'admin-posts-manager'),
			__('APM UI', 'admin-posts-manager'),
			'edit_posts',
			'admin-posts-manager',
			[$this, 'admin_page_callback_posts'],
			'dashicons-admin-post',
			2
		);

		add_submenu_page(
			'admin-posts-manager',
			__('Posts', 'admin-posts-manager'),
			__('Posts', 'admin-posts-manager'),
			'edit_posts',
			'admin-posts-manager',
			[$this, 'admin_page_callback_posts']
		);

		// add_submenu_page(
		// 	'admin-posts-manager',
		// 	__( 'Pages', 'admin-posts-manager' ),
		// 	__( 'Pages', 'admin-posts-manager' ),
		// 	'manage_options',
		// 	'admin-posts-manager-pages',
		// 	[ $this, 'admin_page_callback_posts' ]
		// );
	}

	public function mount_screens()
	{
		Posts::init();
	}

	public function admin_page_callback_posts()
	{
		echo '<div id="apm-posts-root">loading...</div>';
	}
}
