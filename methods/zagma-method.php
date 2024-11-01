<?php

use ZagmaShipping\includes\ZASH_Zagma;
use function ZagmaShipping\ZASH;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( class_exists( 'ZASH_Zagma_Method' ) ) {
	return;
} // Stop if the class already exists

/**
 * Class WC_Zagma_Method
 *
 * @author Zagma *
 */
class ZASH_Zagma_Method extends ZASH_Shipping_Method {

	protected $method = null;

	public function init() {

		parent::init();

		$this->extra_cost = $this->get_option( 'extra_cost', 0 );
		$this->fixed_cost = $this->get_option( 'fixed_cost' );
		$this->pay_type = $this->get_option( 'pay_type' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	public function init_form_fields() {

		$currency_symbol = get_woocommerce_currency_symbol();

		$this->instance_form_fields += [
			'extra_cost' => [
				'title'       => 'هزینه های اضافی',
				'type'        => 'text',
				'description' => 'هزینه های اضافی علاوه بر نرخ پستی را می توانید وارد نمائید، (مثل: هزینه های بسته بندی و ...) مبلغ ثابت را به ' . $currency_symbol . ' وارد نمائید',
				'default'     => 0,
				'desc_tip'    => true,
			],
			'fixed_cost' => [
				'title'       => 'هزینه ثابت',
				'type'        => 'text',
				'description' => "<b>توجه:</b>
								<ul>
									<li>1. برای محاسبه هزینه توسط فرمول زاگما خالی بگذارید.</li>
									<li>2. صفر به معنی رایگان است. یعنی هزینه حمل و نقل برعهده فروشگاه شما است.</li>
									<li>3. در صورت تعیین هزینه ثابت حمل و نقل این قیمت دقیقا به مشتری نمایش داده می شود.</li>
									<li>4. این گزینه مناسب فروشگاه هایی است که وزن محصولات خود را وارد نکرده اند.</li>
								</ul>
								",
				'default'     => '',
				'desc_tip'    => true,
			],
			'pay_type' => [
				'title'       => 'نوع محاسبه هزینه',
				'type'        => 'select',
				'options'     => [ 2 => 'شناور' , 3 => 'مشمول'],
				'description' => "<b>توجه:</b>
				<ul>
					<li>شناور (از خریدار قیمت کالا+هزینه خدمات+هزینه پستی دریافت میگردد)</li>
					<li>مشمول (از خریدار قیمت کالا + هزینه پستی دریافت میگردد)</li>
				</ul>
				",
				'default'     => 2,
				'desc_tip'    => true,
			]
		];
	}
	public function getPayType(){
		return $this->pay_type;
	}
	public function is_available( $package = [] ): bool {
		
		if ( ! ZASH_Zagma::is_enable() ) {
			return false;
		}

		$weight = ZASH_Zagma::get_cart_weight();

		if ( $weight > 30000 ) {
			return false;
		}
		
		return parent::is_available( $package );
	}

	public function calculate_shipping( $package = [] ) {

		if ( $this->free_shipping( $package ) ) {
			return;
		}

		if ( $this->fixed_cost !== '' ) {

			$shipping_total = $this->fixed_cost;

		} else {

			$weight = ZASH_Zagma::get_cart_weight();
			
			$price = 0;

			foreach ( WC()->cart->get_cart() as $cart_item ) {

				if ( $cart_item['data']->is_virtual() ) {
					continue;
				}

				$price += $cart_item['data']->get_price() * $cart_item['quantity'];
			}

			$destination = $package['destination'];

			$payment_method = WC()->session->get( 'chosen_payment_method' );

			$is_cod = $payment_method === 'cod';

			if ( get_woocommerce_currency() == 'IRR' ) {
				$price /= 10;
			}

			if ( get_woocommerce_currency() == 'IRHR' ) {
				$price /= 1000;
			}
			
			$shop = ZASH_Zagma::shop();
			$data = [
				'price'         => intval( $price ),
				'weight'        => ceil( $weight ),
				'method'        => $this->method,
				'is_cod'        => $is_cod,
				'to_province'   => intval( $destination['state'] ),
				'from_province' => intval( $shop->state ?? 1 ),
				'to_city'       => intval( $destination['city'] ),
				'from_city'     => intval( $shop->city ?? 1 ),
				'pay_type' 		=> $this->pay_type
			];

			$cost = $this->calculate_rates( $data );
			

			if ( $cost === false ) {
				return;
			}

			$shipping_total = $cost + ( $shop->total_price ?? 0 );
			if(ZASH()->get_option( 'zagma.round_price' ,false) == 1){
				$shipping_total = ceil( $shipping_total / 1000 ) * 1000;
			}else{
				$shipping_total = ( $shipping_total / 1000 ) * 1000;
			}

			$shipping_total = ZASH()->convert_currency( $shipping_total );

			$shipping_total += $this->extra_cost;
		}

		$this->add_rate_cost( $shipping_total, $package );
	}

	public function calculate_rates( array $args ): int {

		
		$defaults = [
			'price'         => 50000,
			'weight'        => 100,
			'method'        => 'pishtaz',
			'is_cod'        => false,
			'to_province'   => 1,
			'to_city'       => 1,
			'from_province' => 1,
			'from_city'     => 1,
			'pay_type'		=> 3
		];

		$args = wp_parse_args( $args, $defaults );

		
		if ( !$args['is_cod'] ) {
			$cost_type = 'sefareshionline';
			if($args['method'] == 'pishtaz'){
				$cost_type = 'pishtazonline';
			}
		}else{
			$cost_type = 'sefareshipmahal';
			if($args['method'] == 'pishtaz'){
				$cost_type = 'pishtazpmahal';
			}
		}

		$data = [
			'shopkod' =>  ZASH()->get_option( 'zagma.shop_id' ),
			'weight' => $args['weight'],
			'ostan' => $args['to_province'],
			'city' => $args['to_city'],
			'noehazine' => $args['pay_type'],
			'price' => $args['price']
		];
		$response = ZASH_Zagma::request( 'requestpricepostnew', $data,true );
		
		if ( is_wp_error( $response ) || $response['vaz'] != 'true' ) {
			$cost = $this->calculateWithoutWeb($args);
		}else{
			$cost = $response[$cost_type];
			if($cost == 'رایگان'){
				$cost = 0;
			}elseif($cost == 'نامعتبر'){
				$cost = $this->calculateWithoutWeb($args);
			}
		}
		return $cost;
	}
	public function rates( $method ) {

		$rates = [
			'pishtaz'   => [
				500  => [
					'in'     => 57500,
					'beside' => 78000,
					'out'    => 84000,
				],
				1000 => [
					'in'     => 74000,
					'beside' => 100000,
					'out'    => 112000,
				],
				2000 => [
					'in'     => 98000,
					'beside' => 127000,
					'out'    => 140000,
				],
				3000 => [
					'in'     => 123000,
					'beside' => 152000,
					'out'    => 165000,
				],
				4000 => [
					'in'     => 148000,
					'beside' => 177000,
					'out'    => 190000,
				],
				5000 => [
					'in'     => 173000,
					'beside' => 202000,
					'out'    => 215000,
				],
				9999 => 25000,
			],
			'sefareshi' => [
				500  => [
					'in'     => 36800,
					'beside' => 49000,
					'out'    => 53000,
				],
				1000 => [
					'in'     => 48300,
					'beside' => 67600,
					'out'    => 72800,
				],
				2000 => [
					'in'     => 69000,
					'beside' => 88000,
					'out'    => 95000,
				],
				3000 => [
					'in'     => 66700,
					'beside' => 108000,
					'out'    => 116000,
				],
				4000 => [
					'in'     => 66700,
					'beside' => 108000,
					'out'    => 116000,
				],
				5000 => [
					'in'     => 66700,
					'beside' => 108000,
					'out'    => 116000,
				],
				9999 => 10000,
			],
		];

		return $rates[ $method ] ?? false;
	}

	public function calculateWithoutWeb($args){
		$weight_indicator = 9999;

			switch ( true ) {
				case $args['weight'] <= 500:
					$weight_indicator = 500;
					break;
				case $args['weight'] > 500 && $args['weight'] <= 1000:
					$weight_indicator = 1000;
					break;
				case $args['weight'] > 1000 && $args['weight'] <= 2000:
					$weight_indicator = 2000;
					break;
				case $args['weight'] > 2000 && $args['weight'] <= 3000:
					$weight_indicator = 3000;
					break;
				case $args['weight'] > 3000 && $args['weight'] <= 4000:
					$weight_indicator = 4000;
					break;
				case $args['weight'] > 4000 && $args['weight'] <= 5000:
					$weight_indicator = 5000;
					break;
			}
			$args['price'] = $args['price'] * 10;
			$checked_state = ZASH()->check_states_beside( $args['from_province'], $args['to_province'] );

			$rates = $this->rates( $args['method'] );
	
			if ( $rates === false ) {
				return false;
			}
	
			// calculate
			if ( $weight_indicator != 9999 ) {
				$cost = $rates[ $weight_indicator ][ $checked_state ];
			} else {
				$cost = $rates[5000][ $checked_state ] + ( $rates[ $weight_indicator ] * ceil( ( $args['weight'] - 5000 ) / 1000 ) );
			}
	
			if ( in_array( $args['to_city'], [ 1, 31, 51, 81, 71, 91, 61 ] ) ) {
				$cost += $cost * 0.1;
			}
	
			// insurance
			if ( $args['price'] <= 800000 ) {
				$cost += 8000 + 11000;
			} else {
				$cost += $args['price'] * 0.002 + 11000;
			}
	
			if ( $args['is_cod'] ) {
	
				$_rate  = 0.015;
				$_price = 5000;
	
				if ( $checked_state != 'in' ) {
	
					$_rate  = 0.03;
					$_price = 10000;
	
				}
	
				$cost += min( 2000000, $args['price'] ) * $_rate;
	
				$cost += ceil( ( $args['price'] - 2000000 ) / 2000000 ) * $_price;
	
			}
	
			$cost += $cost * 0.09;
			$cost = $cost / 10;
			return $cost;
	}
}
