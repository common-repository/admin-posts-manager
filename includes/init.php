<?php

namespace APM;

use APM\Core\CMS;

class Plugin {

	private static $instance;

	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}


	/**
	 * The Constructor.
	 */
	public function __construct() {
        CMS::init();
	}

}

Plugin::init();