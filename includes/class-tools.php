<?php
namespace ZagmaShipping\includes;

use function ZagmaShipping\ZASH;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class ZASH_Tools {

	public function __construct() {

		if ( ZASH()->get_option( 'zagma.show_credit' ) == 1 ) {
			add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 999 );
		}

		if ( ZASH()->get_option( 'tools.hide_when_free' ) == 1 ) {
			add_filter( 'woocommerce_package_rates', [ $this, 'hide_when_free' ], 100 );
		}

		if ( ZASH()->get_option( 'tools.hide_when_courier' ) == 1 ) {
			add_filter( 'woocommerce_package_rates', [ $this, 'hide_when_courier' ], 100 );
		}

		add_filter( 'woocommerce_new_order_note_data', [ $this, 'new_order_note_data' ], 100, 2 );
	}

	public function admin_bar_menu( $wp_admin_bar ) {


		$message = null;

		$credit = get_transient( 'zash_zagma_credit' );

		if ( $credit === false ) {

			$credit = ZASH_Zagma::request( 'showcredit', [
				'username' => ZASH()->get_option( 'zagma.username' ),
				'password' => ZASH()->get_option( 'zagma.password' ),
			] );
			

			if ( is_wp_error( $credit ) ) {
				$message = $credit->get_error_message();
				$credit  = 'خطا';
			} else if ( $credit->vaz == 'true' ) {
				$credit = wc_price( ZASH()->convert_currency( $credit->usable_credit ?? 0 ) );
				set_transient( 'zash_zagma_credit', $credit, MINUTE_IN_SECONDS * 2 );
			} else {
				$message = $credit->returns->message;
				$credit  = 'خطا';
			}
		}

		$args = [
			'id'    => 'zagma_charge',
			'title' => "اعتبار زاگما: " . $credit,
			'meta'  => [ 'class' => 'zagma' ],
		];

		$wp_admin_bar->add_node( $args );

		if ( ! is_null( $message ) ) {
			$args = [
				'id'     => 'zagma_charge_error',
				'title'  => $message,
				'meta'   => [ 'class' => 'zagma' ],
				'parent' => 'zagma_charge',
			];

			$wp_admin_bar->add_node( $args );
		}
	}

	public function hide_when_free( $rates ) {
		$free = []; // snippets.ir

		foreach ( $rates as $rate_id => $rate ) {
			if ( 0 == $rate->cost ) {
				$free[ $rate_id ] = $rate;
				break;
			}
		}

		return ! empty( $free ) ? $free : $rates;
	}

	public function hide_when_courier( $rates ) {
		$courier = []; // snippets.ir

		foreach ( $rates as $rate_id => $rate ) {
			if ( 'WC_Courier_Method' === $rate->method_id ) {
				$courier[ $rate_id ] = $rate;
				break;
			}
		}

		return ! empty( $courier ) ? $courier : $rates;
	}

	public function new_order_note_data( $data, $args ) {

		$barcode = trim( $data['comment_content'] );

		if ( is_numeric( $barcode ) && strlen( $barcode ) >= 10 ) {
			$data['comment_content'] =  "کد رهگیری مرسوله شما: {$barcode}
			می توانید مرسوله خود را از طریق لینک 
			https://www.zagma.ir/order-tracking/?id={$barcode}
			 رهگیری نمایید.";


			update_post_meta( $args['order_id'], 'post_barcode', $barcode );
			$order = new WC_Order( $args['order_id'] );

			do_action( 'zash_save_order_post_barcode', $order, $barcode );
		}

		return $data;
	}
}

new ZASH_Tools();
