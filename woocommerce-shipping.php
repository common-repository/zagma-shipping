<?php
/**
 * Plugin Name: zagma shipping
 * Plugin URI: https://www.zagma.ir/پلاگین/
 * Description: افزونه قدرتمند حمل و نقل ووکامرس با قابلیت ارسال از طریق پست پیشتاز، سفارشی و ... بدون محدودیت در نوع کالا به سراسر ایران، یک دفتر پستی اختصاصی در هر جایی که مایل بودید ایجاد نمایید، محاسبه دقیق هزینه پستی، جمع آوری سفارشات از محل شما، صدور فاکتور آنی، ارسال کد رهگیری پست به مشتری و ....
 * Version: 1.1.0
 * Author: Zagma
 * Author URI: http://zagma.ir
 * WC requires at least: 4.0.0
 * WC tested up to: 4.9.0
 */
namespace ZagmaShipping;

use ZagmaShipping\includes\ZASH_Core;
use ZagmaShipping\includes\ZASH_Zagma;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! defined( 'ZASH_DIR' ) ) {
	define( 'ZASH_DIR', __DIR__ );
}

if ( ! defined( 'ZASH_FILE' ) ) {
	define( 'ZASH_FILE', __FILE__ );
}

if ( ! defined( 'ZASH_URL' ) ) {
	define( 'ZASH_URL', plugin_dir_url( __FILE__ ) );
}
if (! function_exists( 'ZASH' ) ) {
	function ZASH() {

		if ( ZASH_Zagma::is_enable() ) {
			return ZASH_Zagma::instance();
		}

		return ZASH_Core::instance();
	}
}
add_action( 'woocommerce_loaded', function () {

	include( "includes/class-zash.php" );
	include( "includes/class-ajax.php" );
	include( "includes/class-zagma.php" );
	include( "includes/class-order.php" );
	include( "includes/class-tools.php" );
	include( "includes/class-status.php" );
	include( "includes/admin/class-admin.php" );


} );




