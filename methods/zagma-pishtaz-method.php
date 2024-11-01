<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( class_exists( 'Zagma_Pishtaz_Method' ) ) {
	return;
} // Stop if the class already exists

/**
 * Class WC_Zagma_Method
 *
 * @author Zagma *
 */
class Zagma_Pishtaz_Method extends ZASH_Zagma_Method {

	protected $method = 'pishtaz';

	public function __construct( $instance_id = 0 ) {

		$this->id                 = 'Zagma_Pishtaz_Method';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'پست زاگما - پیشتاز' );
		$this->method_description = 'پیشخوان مجازی زاگما - ارسال کالا با استفاده از پست پیشتاز';

		parent::__construct();
	}


}
