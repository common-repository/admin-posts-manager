<?php

namespace APM\Core\Admin\Apps;

use APM\Core\Admin\Utils;

class Posts
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
		add_action('current_screen', [$this, 'instantiate']);

		// ajax
		add_action('wp_ajax_apm_get_posts', [$this, 'apm_get_posts']);
		add_action('wp_ajax_apm_trash_post', [$this, 'trash_post']);
		add_action('wp_ajax_apm_restore_post', [$this, 'restore_post']);
		add_action('wp_ajax_apm_delete_post', [$this, 'delete_post']);
		add_action('wp_ajax_apm_update_post', [$this, 'update_post']);
	}


	public function instantiate($screen)
	{
		if (!$screen) {
			return;
		}

		if ($screen->id === 'toplevel_page_admin-posts-manager') {
			// actions
			add_action('admin_enqueue_scripts', [$this, 'admin_scripts'], 11, 1);
		}
	}

	public function admin_scripts()
	{
		$this->enqueue_scripts('posts');
		$this->enqueue_styles('posts');
		// wp_enqueue_media();
		// wp_enqueue_editor();
		// wp_dequeue_script( 'autosave' );
	}

	private function enqueue_scripts($name)
	{
		$filepath   = 'dist/' . $name;
		$asset_file = Utils::get_asset_file($filepath);
		$post_id    = get_the_ID();
		$handle     = 'wpv-me-' . $name . '-script';

		array_push($asset_file['dependencies'], 'lodash');

		wp_register_script(
			$handle,
			WP_APM_URL . $filepath . '.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		$wme_variable                   = Utils::get_default_global_variables();
		$posts_count = wp_count_posts('post', 'readable');
		$posts_count->mine = $this->get_current_user_posts_count();
		$wme_variable['posts_count'] = $posts_count;
		$wme_variable['posts']      = $this->get_initial_posts()['posts'];
		$wme_variable['initial_posts_count'] = $this->get_initial_posts()['initial_posts_count'];


		$wme_variable                   = apply_filters('apm_page_variable', $wme_variable);

		wp_localize_script(
			$handle,
			'wp_apm',
			$wme_variable
		);

		wp_enqueue_script($handle);
	}

	private function get_current_user_posts_count()
	{
		$user_id = get_current_user_id();
		$args    = [
			'author' => $user_id,
			'post_type' => 'post',
			'post_status' => Utils::get_all_post_status(),
			'posts_per_page' => -1,
		];

		$query = new \WP_Query($args);

		return $query->found_posts;
	}

	private function get_posts_count($args)
	{
		$args['posts_per_page'] = -1;
		$query = new \WP_Query($args);

		return $query->found_posts;
	}

	private function enqueue_styles($name)
	{
		$filepath   = 'dist/' . $name;
		$asset_file = Utils::get_asset_file($filepath);
		$handle     = 'wpv-me-' . $name . '-script';
		wp_enqueue_style(
			$handle,
			WP_APM_URL . $filepath . '.css',
			['wp-edit-blocks'],
			$asset_file['version']
		);
	}

	public function trash_post()
	{
		if (!wp_verify_nonce($_POST['ajax_nonce'], 'apm_ajax_nonce')) {
			wp_send_json_error(['message' => 'Sorry, your nonce did not verify!']);
			exit;
		}

		$post_id = sanitize_text_field($_POST['post_id']);

		if (!$post_id) {
			wp_send_json_error(['message' => 'Post id is required']);
		}

		$post = get_post($post_id);

		if (!$post) {
			wp_send_json_error(['message' => 'Post not found']);
		}

		if ($post->post_type !== 'post') {
			wp_send_json_error(['message' => 'Post type is not post']);
		}

		if (
			$post->post_status !== 'publish' &&
			$post->post_status !== 'future' &&
			$post->post_status !== 'draft' &&
			$post->post_status !== 'pending' &&
			$post->post_status !== 'private' &&
			$post->post_status !== 'auto-draft'
		) {
			wp_send_json_error(['message' => 'Post status is not publish, future, draft, pending, private or auto-draft']);
		}

		if (!current_user_can('delete_post', $post_id)) {
			wp_send_json_error(['message' => 'You are not allowed to delete this post']);
		}

		try {
			$is_trashed = wp_trash_post($post_id);

			if ($is_trashed) {
				wp_send_json_success(['message' => 'Post trashed']);
			} else {
				wp_send_json_error(['message' => 'Post not trashed']);
			}
		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	public function restore_post()
	{
		if (!wp_verify_nonce($_POST['ajax_nonce'], 'apm_ajax_nonce')) {
			wp_send_json_error(['message' => 'Sorry, your nonce did not verify!']);
			exit;
		}

		$post_id = sanitize_text_field($_POST['post_id']);

		if (!$post_id) {
			wp_send_json_error(['message' => 'Post id is required']);
		}

		$post = get_post($post_id);

		if (!$post) {
			wp_send_json_error(['message' => 'Post not found']);
		}

		if ($post->post_type !== 'post') {
			wp_send_json_error(['message' => 'Post type is not post']);
		}

		if (
			$post->post_status !== 'trash'
		) {
			wp_send_json_error(['message' => 'Post status is not trash']);
		}

		if (!current_user_can('delete_post', $post_id)) {
			wp_send_json_error(['message' => 'You are not allowed to delete this post']);
		}

		try {
			$is_restored = wp_untrash_post($post_id);

			if ($is_restored) {
				wp_send_json_success(['message' => 'Post restored']);
			} else {
				wp_send_json_error(['message' => 'Post not restored']);
			}
		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	public function delete_post()
	{
		if (!wp_verify_nonce($_POST['ajax_nonce'], 'apm_ajax_nonce')) {
			wp_send_json_error(['message' => 'Sorry, your nonce did not verify!']);
			exit;
		}

		$post_id = sanitize_text_field($_POST['post_id']);

		if (!$post_id) {
			wp_send_json_error(['message' => 'Post id is required']);
		}

		$post = get_post($post_id);

		if (!$post) {
			wp_send_json_error(['message' => 'Post not found']);
		}

		if ($post->post_type !== 'post') {
			wp_send_json_error(['message' => 'Post type is not post']);
		}

		if (
			$post->post_status !== 'trash'
		) {
			wp_send_json_error(['message' => 'Post status is not trash']);
		}

		if (!current_user_can('delete_post', $post_id)) {
			wp_send_json_error(['message' => 'You are not allowed to delete this post']);
		}

		try {
			$is_deleted = wp_delete_post($post_id, true);

			if ($is_deleted) {
				wp_send_json_success(['message' => 'Post deleted']);
			} else {
				wp_send_json_error(['message' => 'Post not deleted']);
			}
		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	public function update_post()
	{
		if (!wp_verify_nonce($_POST['ajax_nonce'], 'apm_ajax_nonce')) {
			wp_send_json_error(['message' => 'Sorry, your nonce did not verify!']);
			exit;
		}

		$post_id = sanitize_text_field($_POST['post_id']);

		if (!$post_id) {
			wp_send_json_error(['message' => 'Post id is required']);
		}

		$post = get_post($post_id);

		if (!$post) {
			wp_send_json_error(['message' => 'Post not found']);
		}

		if ($post->post_type !== 'post') {
			wp_send_json_error(['message' => 'Post type is not post']);
		}

		$updated_data = (array) Utils::sanitize_text_or_array_field(json_decode(stripslashes($_POST['post_data'])));

		if (!$updated_data) {
			wp_send_json_error(['message' => 'Post data is required']);
		}

		if (!current_user_can('edit_post', $post_id)) {
			wp_send_json_error(['message' => 'You are not allowed to edit this post']);
		}



		// echo '<pre>';
		// print_r($updated_data);
		// echo '</pre>';
		// die();

		try {

			$post_title = $updated_data['post_title'];
			$post_author = $updated_data['post_author'];
			$post_status = $updated_data['post_status'];
			$comment_status = $updated_data['comment_status'];
			$post_password = $updated_data['post_password'];
			$post_name = $updated_data['post_name'];
			$date = $updated_data['date'];
			$categories = $updated_data['categories'];
			$ping_status = $updated_data['ping_status'];
			$tags = $updated_data['tags'];
			$is_sticky = $updated_data['is_sticky'];
			$day = $date->day;
			$month = $date->month;
			$year = $date->year;
			$hour = $date->hour;
			$minute = $date->minute;
			$time = current_time('timestamp');


			$date_args = "$year-$month-$day $hour:$minute:" . date('s', $time);
			$post_date = date('Y-m-d H:i:s', strtotime($date_args, $time));
			$post_date_gmt = gmdate('Y-m-d H:i:s', strtotime($date_args, $time));



			$post_data = [
				'ID' => $post_id,
				'post_title' => $post_title,
				'post_author' => $post_author,
				'post_status' => $post_status,
				'comment_status' => $comment_status,
				'post_password' => $post_password,
				'post_name' => $post_name,
				'post_date' => $post_date,
				'post_date_gmt' => $post_date_gmt,
				'post_category' => $categories,
				'tags_input' => $tags,
				'ping_status' => $ping_status,
			];

			$is_updated = wp_update_post($post_data);

			if ($is_updated) {
				// update post sticky
				if ($is_sticky) {
					stick_post($post_id);
				} else {
					unstick_post($post_id);
				}
				wp_send_json_success([
					'message' => 'Post updated',
					'data' => [
						'updated_date' => date('F j, Y g:i a', strtotime($post_date)),
						'tags' => get_the_tags($post_id),
					]
				]);
			} else {
				wp_send_json_error(['message' => 'Post not updated']);
			}
		} catch (\Exception $e) {
			wp_send_json_error(['message' => $e->getMessage()]);
		}
	}

	public function apm_get_posts()
	{
		if (!wp_verify_nonce($_POST['ajax_nonce'], 'apm_ajax_nonce')) {
			wp_send_json_error(['message' => 'Sorry, your nonce did not verify!']);
			exit;
		}

		$params = (array) Utils::sanitize_text_or_array_field(json_decode(stripslashes($_POST['params'])));

		$page_size = $params['page_size'] ?: 10;
		$post_filter = $params['post_filter'] ?: 'any';
		$author_id =  $params['author_id'] ?: null;
		$category_id =  $params['category_id'] ?: null;
		$tag_id =  $params['tag_id'] ?: null;
		$page = $params['page'] ?: 1;
		$order = $params['order'] ?: 'DESC';
		$orderby = $params['order_by'] ?: 'date';
		$search = $params['search'] ?: '';
		$date = '';

		if (isset($params['date_range'])) {
			$date = (array) $params['date_range'];
		}

		if ($order == 'asc') {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		if ($post_filter === 'any' || $post_filter === 'mine' || $post_filter === '' || $post_filter === 'custom') {
			$post_filter = Utils::get_all_post_status();
		}

		$args = [
			'post_type' => 'post',
			'post_status' => $post_filter,
			'paged' => $page,
			'posts_per_page' => $page_size,
			'author' => $author_id,
			'cat' => $category_id,
			'tag_id' => $tag_id,
			'order' => $order,
			'orderby' => $orderby,
			's' => $search,
		];

		if ($date && is_array($date) && count($date) > 0) {
			$args['date_query'] = [
				[
					'after' => [
						'year' => $date['from']->year,
						'month' => $date['from']->month,
						'day' => $date['from']->day,
					],
					'before' => [
						'year' => $date['to']->year,
						'month' => $date['to']->month,
						'day' => $date['to']->day,
					],
					'inclusive' => true,
				],
			];
		}


		if ($params['post_filter'] === 'mine') {
			$args['author'] = get_current_user_id();
		}

		$args = apply_filters('apm_get_posts_args', $args);

		$posts = get_posts($args);

		$response = [];

		if (!empty($posts)) {
			$response = [
				'status' => 'success',
				'posts' => $this->with_extra_info_to_posts($posts),
				'total_count' => $this->get_posts_count($args),
			];
		} else {
			$response = [
				'status' => 'not_found',
				'message' => 'No posts found',
				'total_count' => $this->get_posts_count($args),
			];
		}

		wp_send_json($response);
	}

	private function get_initial_posts()
	{

		$posts_per_page = Utils::get_default_global_variables()['page_size_default'];
		$initial_date_range = Utils::get_default_global_variables()['initial_date_range'];

		$args = [
			'posts_per_page' => $posts_per_page,
			'post_type'      => 'post',
			'post_status'    =>  Utils::get_all_post_status(),
			'order'          => 'DESC',
			'orderby'        => 'date',
			'date_query' => [
				[
					'after' => [
						'year' => $initial_date_range['from']['year'],
						'month' => $initial_date_range['from']['month'],
						'day' => $initial_date_range['from']['day'],
					],
					'before' => [
						'year' => $initial_date_range['to']['year'],
						'month' => $initial_date_range['to']['month'],
						'day' => $initial_date_range['to']['day'],
					],
					'inclusive' => true,
				],
			]
		];

		$args = apply_filters('apm_initial_get_posts_args', $args);

		$posts = get_posts($args);

		// echo '<pre>';
		// print_r($this->with_extra_info_to_posts($posts));
		// echo '</pre>';
		// die();

		return [
			'posts' => $this->with_extra_info_to_posts($posts),
			'initial_posts_count' => $this->get_posts_count($args)
		];
	}

	// private function get_posts_by_custom_query($args, $filters)
	// {
	// 	global $wpdb;

	// 	$query = "SELECT * FROM {$wpdb->posts} WHERE POST_TYPE = '{$args['post_type']}' AND 1=1";

	// 	foreach ($filters as $filter) {
	// 		$filter = (array) $filter;
	// 		$col_name = strtoupper(sanitize_text_field($filter['key']));
	// 		$operator = sanitize_text_field($filter['operator']);
	// 		$value = sanitize_text_field($filter['value']);

	// 		$query .= " AND {$col_name} {$operator} '{$value}'";
	// 	}

	// 	$query .= " ORDER BY post_{$args['orderby']} {$args['order']} LIMIT {$args['posts_per_page']}";



	// 	$posts = $wpdb->get_results(
	// 		$query,
	// 		ARRAY_A
	// 	);

	// 	print_r($query);
	// 	print_r($args);
	// 	print_r($posts);
	// 	die();

	// 	return $posts;
	// }

	private function with_extra_info_to_posts($posts)
	{
		foreach ($posts as $key => $post) {
			$posts[$key]->author_name 		= get_the_author_meta('display_name', $post->post_author);
			$posts[$key]->categories  		= get_the_category($post->ID);
			$posts[$key]->tags        		= get_the_tags($post->ID);
			$posts[$key]->formatted_date   	= date('F j, Y g:i a', strtotime($post->post_date));
			$posts[$key]->post_sticky = is_sticky($post->ID);
		}

		return apply_filters('apm_after_with_extra_info_data', $posts);
	}
}
