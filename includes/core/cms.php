<?php

namespace APM\Core;

use APM\Core\Admin\Admin;

class CMS
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
		// if (is_admin()) {
		Admin::init();
		// }
	}
}
