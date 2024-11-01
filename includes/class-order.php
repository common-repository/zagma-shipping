<?php

namespace ZagmaShipping\includes;
use function ZagmaShipping\ZASH;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class ZASH_Order {

	public static function get_order_weight(  $order ) {
		$order = wc_get_order($order);
		$weight = $order->get_meta( 'zagma_weight' );

		if ( $weight != '' ) {
			return $weight;
		}

		$weight = ZASH()->get_option( 'zagma.package_weight', 500 );

		foreach ( $order->get_items() as $order_item ) {

			/** @var WC_Product $product */
			$product = $order_item->get_product();

			if ( is_bool( $product ) || $product->is_virtual() ) {
				continue;
			}

			if ( $product->has_weight() ) {
				$_weight = wc_get_weight( $product->get_weight(), 'g' );
			} else {
				$_weight = ZASH()->get_option( 'zagma.product_weight', 500 );
			}

			$weight += $_weight * $order_item->get_quantity();
		}

		return $weight;
	}

	public static function get_shipping_method(  $order, $label = false ) {

		$shipping_method = null;
		$order = wc_get_order($order);
		foreach ( $order->get_shipping_methods() as $shipping_item ) {
			if ( strpos( $shipping_item->get_method_id(), 'Zagma_Pishtaz_Method' ) === 0 ) {
				$shipping_method = 2;
			} else if ( strpos( $shipping_item->get_method_id(), 'Zagma_Sefareshi_Method' ) === 0 ) {
				$shipping_method = 1;
			}else if ( strpos( $shipping_item->get_method_id(), 'free_shipping' ) === 0 ) {
				$shipping_method = 'free';
			}
		}

		$labels = [
			'سفارشی',
			'پیشتاز',
		];

		if ( $label ) {
			return $labels[ $shipping_method ] ?? null;
		}

		return $shipping_method;
	}
	public static function get_Pay_Type_Method($method){
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name LIKE '%$method%' LIMIT 1") );
		if(isset($row->option_value)){
			$row = unserialize($row->option_value);
			if(isset($row['pay_type'])){
				return $row['pay_type'];
			}
		}
		return 3;
	}

}
