<?php
namespace ZagmaShipping\includes\admin;

class ZASH_Admin {

	public function __construct() {

		$this->includes();

		add_action( 'admin_menu', [ $this, 'admin_menu' ], 20 );

		add_filter( 'parent_file', [ $this, 'parent_file' ] );
	}

	public function admin_menu() {

		$capability = apply_filters( 'zash_menu_capability', 'manage_woocommerce' );

		add_menu_page( 'حمل و نقل زاگما', 'حمل و نقل زاگما', $capability, 'zash-tools', [
			ZASH_Settings_Tools::class,
			'output',
		], ZASH_URL . 'assets/images/zash.png', '55.8' );

		$submenus = [
			10 => [
				'title'      => 'ابزارها',
				'capability' => $capability,
				'slug'       => 'zash-tools',
				'callback'   => [ ZASH_Settings_Tools::class, 'output' ],
			],
			20 => [
				'title'      => 'تنظیمات اتصال',
				'capability' => $capability,
				'slug'       => 'zash-zagma',
				'callback'   => [ ZASH_Settings_Zagma::class, 'output' ],
			]
		];


		$submenus = apply_filters( 'zash_submenu', $submenus );

		foreach ( $submenus as $submenu ) {
			add_submenu_page( 'zash-tools', $submenu['title'], $submenu['title'], $submenu['capability'], $submenu['slug'], $submenu['callback'] );

			add_action( 'admin_init', function () use ( $submenu ) {
				if ( isset( $submenu['callback'][0] ) && class_exists( $submenu['callback'][0] ) ) {
					call_user_func( [ $submenu['callback'][0], 'instance' ] );
				}
			}, 5 );
		}

	}

	public function parent_file( $parent_file ) {

		if ( ! isset( $_GET['taxonomy'] ) || $_GET['taxonomy'] != 'state_city' ) {
			return $parent_file;
		}

		return 'zash-tools';
	}

	public function includes() {
		include 'class-settings.php';
		include 'class-zagma.php';
		include 'class-tools.php';
	}

}

new ZASH_Admin();
