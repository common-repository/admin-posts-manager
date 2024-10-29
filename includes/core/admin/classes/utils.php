<?php

namespace APM\Core\Admin;

class Utils
{
	public static function get_asset_file($filepath)
	{
		$asset_path = WP_APM_PATH . $filepath . '.asset.php';

		return file_exists($asset_path)
			? include $asset_path
			: [
				'dependencies' => [],
				'version'      => WP_APM_VERSION,
			];
	}

	public static function get_last_30_days_range()
	{
		$endDate = new \DateTime();
		$startDate = new \DateTime('-30 days');

		return [
			'from' => [
				'year' => $startDate->format('Y'),
				'month' => $startDate->format('m'),
				'day' => $startDate->format('d'),
			],
			'to' => [
				'year' => $endDate->format('Y'),
				'month' => $endDate->format('m'),
				'day' => $endDate->format('d'),
			]
		];
	}

	public static function get_all_post_status()
	{
		return [
			'publish',
			'pending',
			'draft',
			'future',
			'private',
			'inherit',
			'trash',
			'any'
		];
	}

	public static function get_default_global_variables()
	{

		$last_30_days_range = self::get_last_30_days_range();

		return apply_filters('apm_default_global_variables', [
			'initial_date_range' => $last_30_days_range,
			'rest_nonce' => wp_create_nonce('apm__rest_nonce'),
			'ajax_nonce' => wp_create_nonce('apm_ajax_nonce'),
			'ajax_url'   => admin_url('admin-ajax.php'),
			'rest_url'   => get_rest_url(),
			'site_url'   => site_url(),
			'current_user_id' => get_current_user_id(),
			'admin_url' => admin_url(),
			'page_size' => [5, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
			'page_size_default' => 10,
			'pagination_on_above_table' => false,
			'need_table_footer' => false,
			'post_filters' => [
				[
					'label' => 'is',
					'operator' => '=',
				],
				[
					'label' => 'is not',
					'operator' => '!=',
				],
				[
					'label' => 'contains',
					'operator' => 'like',
				],
				[
					'label' => 'not contain',
					'operator' => 'not like',
				],
				[
					'label' => 'starts with',
					'operator' => 'sw',
				],
				[
					'label' => 'ends with',
					'operator' => 'ew',
				],
			],
			// supported date formats -> https://date-fns.org/v2.28.0/docs/format
			'date_format' => 'dd/MM/yyyy',
			'plugin_review_url' => 'https://wordpress.org/support/plugin/admin-posts-manager/reviews/#new-post',
			'plugin_support_url' => 'https://wordpress.org/support/plugin/admin-posts-manager/',
			'search_input_debounce_time' => 500,
			'post_quick_actions' => [
				'edit' => 'Edit',
				'quick_edit' => 'Quick Edit',
				'trash' => 'Trash',
				'preview' => 'Preview',
				'restore' => 'Restore',
				'delete' => 'Delete Permanently',
			],
			'authors' => self::get_authors(),
			'categories' => self::get_categories(),
		]);
	}

	public static function get_categories()
	{
		$categories = get_categories([
			'hide_empty' => false,
		]);

		$categories_list = [];
		foreach ($categories as $category) {
			$categories_list[] = [
				'id' => $category->term_id,
				'name' => $category->name,
				'slug' => $category->slug,
			];
		}

		return $categories_list;
	}

	public static function get_authors()
	{
		// get all the users except the subscribers
		$authors = get_users([
			'fields' => ['ID', 'display_name'],
			'orderby' => 'display_name',
			'order' => 'DESC',
			'role__not_in' => ['subscriber'],
		]);

		// add user avatar url
		array_walk($authors, function (&$author) {
			$author->avatar_url = get_avatar_url($author->ID);
			$user = get_user_by('id', $author->ID);
			$author->role = $user->roles[0];
		});



		return $authors;
	}

	public static function is_admin()
	{
		return current_user_can('manage_options');
	}

	public static function get_screens()
	{
		return apply_filters('apm_screens', [
			'toplevel_page_admin-posts-manager'
		]);
	}

	public static function get_plugin_name()
	{
		return 'Admin Posts Manager';
	}

	public static function get_text_domain()
	{
		return 'wp-apm';
	}

	public static function sanitize_text_or_array_field($array_or_string)
	{
		if (is_string($array_or_string)) {
			$array_or_string = sanitize_text_field($array_or_string);
		} elseif (is_array($array_or_string)) {
			foreach ($array_or_string as $key => &$value) {
				if (is_array($value)) {
					$value = self::sanitize_text_or_array_field($value);
				} else {
					$value = sanitize_text_field($value);
				}
			}
		}

		return $array_or_string;
	}
}
