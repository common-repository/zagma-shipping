<?php

namespace ZagmaShipping\includes;
use function ZagmaShipping\ZASH;

class ZASH_Zagma extends ZASH_Core {

	

	/**
	 * Ensures only one instance of ZASH_Zagma is loaded or can be loaded.
	 *
	 * @return ZASH_Zagma
	 * @see ZASH()
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		self::$methods = [
			'Zagma_Sefareshi_Method',
			'Zagma_Pishtaz_Method',
		];

		add_filter( 'wooi_ticket_header_path', function () {
			return ZASH_DIR . '/assets/template/header.php';
		}, 100 );
		add_filter( 'wooi_ticket_body_path', function () {
			return ZASH_DIR . '/assets/template/body.php';
		}, 100 );
		add_filter( 'wooi_ticket_footer_path', function () {
			return ZASH_DIR . '/assets/template/footer.php';
		}, 100 );
		add_filter( 'wooi_ticket_per_page', function () {
			return 10000;
		}, 100 );

		add_action( 'admin_footer', [ $this, 'admin_footer' ] );

		parent::init_hooks();
	}

	public function state_city_admin_menu() {
		// Hide menu
	}

	public function enqueue_select2_scripts() {
		if ( ! is_checkout() ) {
			return false;
		}

		wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full.min.js', [ 'jquery' ], '4.0.3' );
		wp_enqueue_script( 'selectWoo' );
		wp_register_style( 'select2', WC()->plugin_url() . '/assets/css/select2.css' );
		wp_enqueue_style( 'select2' );

		wp_register_script( 'zashCheckout', ZASH_URL . 'assets/js/zash-zagma.js', [ 'selectWoo' ], '1.0.0' );
		wp_localize_script( 'zashCheckout', 'zash_settings', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'types'    => $this->types(),
			'is_cod'   => WC()->session->get( 'chosen_payment_method' ) == 'cod',
		] );
		wp_enqueue_script( 'zashCheckout' );
	}

	public function checkout_update_order_meta( $order_id ) {

		$types  = $this->types();
		$fields = [ 'state', 'city' ];

		foreach ( $types as $type ) {

			foreach ( $fields as $field ) {

				$term_id = get_post_meta( $order_id, "_{$type}_{$field}", true );
				$term    = self::{'get_' . $field}( intval( $term_id ) );

				if ( ! is_null( $term ) ) {
					update_post_meta( $order_id, "_{$type}_{$field}", $term );
					update_post_meta( $order_id, "_{$type}_{$field}_id", $term_id );
				}

			}
		}

		if ( wc_ship_to_billing_address_only() ) {

			foreach ( $fields as $field ) {

				$label = get_post_meta( $order_id, "_billing_{$field}", true );
				$id    = get_post_meta( $order_id, "_billing_{$field}_id", true );

				update_post_meta( $order_id, "_shipping_{$field}", $label );
				update_post_meta( $order_id, "_shipping_{$field}_id", $id );

			}

		}

		/** @var WC_Order $order */
		$order = wc_get_order( $order_id );

		foreach ( $order->get_shipping_methods() as $shipping_item ) {

			if ( in_array( $shipping_item->get_method_id(), [ 'Zagma_Pishtaz_Method', 'Zagma_Sefareshi_Method' ] ) ) {

				$instance_id = $shipping_item->get_instance_id();

				$data = get_option( "woocommerce_{$shipping_item->get_method_id()}_{$instance_id}_settings" );

				$packaging_cost = intval( $data['extra_cost'] ?? 0 );

				if ( $shipping_item->get_total() && $packaging_cost ) {
					update_post_meta( $order_id, 'packaging_cost', $packaging_cost );
				}
			}
		}
	}

	public function checkout_process() {

		$types = $this->types();

		$fields = [
			'state' => 'استان',
			'city'  => 'شهر',
		];

		$type_label = [
			'billing'  => 'صورتحساب',
			'shipping' => 'حمل و نقل',
		];

		if ( ! isset( $_POST['ship_to_different_address'] ) && count( $types ) == 2 ) {
			unset( $types[1] );
		}

		foreach ( $types as $type ) {

			$label = $type_label[ $type ];

			foreach ( $fields as $field => $name ) {

				$key = $type . '_' . $field;

				if ( isset( $_POST[ $key ] ) && strlen( $_POST[ $key ] ) ) {

					$value = intval( $_POST[ $key ] );

					if ( $value == 0 ) {
						$message = sprintf( 'لطفا <b>%s %s</b> خود را انتخاب نمایید.', $name, $label );
						wc_add_notice( $message, 'error' );

						continue;
					}

					$invalid = is_null( self::{'get_' . $field}( $value ) );

					if ( $invalid ) {
						$message = sprintf( '<b>%s %s</b> انتخاب شده معتبر نمی باشد.', $name, $label );
						wc_add_notice( $message, 'error' );

						continue;
					}

					if ( $field == 'state' ) {

						$pkey = $type . '_city';

						$cities = self::cities( $value );

						if ( isset( $_POST[ $pkey ] ) && ! empty( $_POST[ $pkey ] ) && ! isset( $cities[ $_POST[ $pkey ] ] ) ) {
							$message = sprintf( '<b>استان</b> با <b>شهر</b> %s انتخاب شده همخوانی ندارند.', $label );
							wc_add_notice( $message, 'error' );

							continue;
						}
					}

				}

			}

		}
	}

	public function cart_shipping_packages( $packages ) {

		for ( $i = 0; $i < count( $packages ); $i ++ ) {
			$packages[ $i ]['destination']['is_cod'] = WC()->session->get( 'chosen_payment_method' ) == 'cod';
		}

		return $packages;
	}

	public function localisation_address_formats( $formats ) {

		$formats['IR'] = "{company}\n{first_name} {last_name}\n{country}\n{state}\n{city}\n{address_1}\n{address_2}\n{postcode}";

		return $formats;
	}

	public function formatted_address_replacements( $replace, $args ) {

		$replace = parent::formatted_address_replacements( $replace, $args );

		if ( ctype_digit( $args['city'] ) ) {
			$city              = $this->get_city( $args['city'] );
			$replace['{city}'] = is_null( $city ) ? $args['city'] : $city;
		}

		return $replace;
	}

	public function admin_footer() {

		if ( ! isset( $_GET['page'], $_GET['tab'], $_GET['instance_id'] ) || $_GET['tab'] != 'shipping' ) {
			return false;
		}

		?>
        <script type="text/javascript">

            let tipax = jQuery("#woocommerce_WC_Tipax_Method_destination");

            if (tipax.length) {
                tipax.select2();
            }
        </script>
		<?php
	}

	public static function is_enable() {
		return self::get_option( 'zagma.enable', false ) == 1;
	}

	public static function request( $path, $datas = [], $arr = false ) {

		if(self::get_option( 'zagma.connection', false ) == 1 && isset($datas['username'])){
			unset($datas['username']);
			unset($datas['password']);
			$datas['token'] = self::get_option( 'zagma.token', false );
		}
		
		$path = trim( $path, ' / ' );

		
		$url = sprintf( 'http://api.zagma.ir/%s', $path );
		if(self::get_option( 'tools.zagma_server' ) == 2){
			$url = sprintf( 'http://api.zagma.org/%s', $path );
		}
		$args = array(
			'body'        => json_encode($datas),
			'headers'     => array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen(json_encode($datas))
				)
		);
		$response = wp_remote_post( $url, $args );
		$response = wp_remote_retrieve_body($response);
		return json_decode( $response,$arr );
	}

	public static function zone() {
		
		$zone = get_transient( 'zash_zagma_zone' );
		
		if ( $zone === false || count( (array) $zone ) == 0 ) {

			if(self::get_option( 'tools.load_city_list' ) == 1){
				$zone = file_get_contents( ZASH_DIR . '/data/zagma.json' );
				$zone = self::processZone($zone);
				return $zone;
			}
			$url = 'http://api.zagma.ir/cityfull';
			if(self::get_option( 'tools.zagma_server' ) == 2){
				$url = 'http://api.zagma.org/cityfull';
			}
			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) ) {
				$zone = get_option( 'zash_zagma_zone', null );

				if ( is_null( $zone ) ) {
					$zone = file_get_contents( ZASH_DIR . '/data/zagma.json' );
					$zone = self::processZone($zone);
				}

			} else {
				$data = $response['body'];
				$zone = self::processZone($data);
			}
		}

		return $zone;
	}

	public static function processZone($data){
		$data = json_decode( $data, true )['entries'];

		$zone = [];
		foreach ( $data as $state ) {
			$zone[ $state['code'] ] = [
				'title'  => trim( $state['title'] ),
				'cities' => [],
			];

			foreach ( $state['cities'] as $city ) {
				$title = trim( str_replace( '-' . $state['title'], '', $city['title'] ) );

				$zone[ $state['code'] ]['cities'][ $city['code'] ] = $title;
			}
		}
		set_transient( 'zash_zagma_zone', $zone, WEEK_IN_SECONDS );
		update_option( 'zash_zagma_zone', $zone );
		return $zone;
	}
	public static function states() {

		$states = get_transient( 'zash_zagma_states' );

		if ( $states === false || count( (array) $states ) == 0 ) {

			$zone = self::zone();

			$states = [];

			foreach ( $zone as $code => $state ) {
				$states[ $code ] = trim( $state['title'] );
			}

			uasort( $states, [ self::class, 'zash_sort_state' ] );

			set_transient( 'zash_zagma_states', $states, DAY_IN_SECONDS );
		}

		return apply_filters( 'zash_states', $states );
	}

	public static function cities( $state_id = null ) {

		$cities = get_transient( 'zash_zagma_cities_' . $state_id );
		//delete_transient('zash_zagma_cities_' . $state_id);
		if ( $cities === false || count( (array) $cities ) == 0 ) {

			$zone = self::zone();

			if ( is_null( $state_id ) ) {

				$state_cities = array_column( self::zone(), 'cities' );

				$cities = [];

				foreach ( $state_cities as $state_city ) {
					$cities += $state_city;
				}

			} else if ( isset( $zone[ $state_id ]['cities'] ) ) {
				$cities = $zone[ $state_id ]['cities'];

				asort( $cities );
			} else {
				return [];
			}

			set_transient( 'zash_zagma_cities_' . $state_id, $cities, DAY_IN_SECONDS );
		}

		return apply_filters( 'zash_cities', $cities, $state_id );
	}

	public static function get_city( $city_id ) {

		$cities = self::cities();

		return $cities[ $city_id ] ?? null;
	}

	public function check_states_beside( $source, $destination ) {

		if ( $source == $destination ) {
			return 'in';
		}

		$is_beside[3][16] = true;
		$is_beside[3][15] = true;
		$is_beside[3][12] = true;

		$is_beside[16][3]  = true;
		$is_beside[16][18] = true;
		$is_beside[16][12] = true;

		$is_beside[15][3]  = true;
		$is_beside[15][2]  = true;
		$is_beside[15][12] = true;

		$is_beside[6][24] = true;
		$is_beside[6][20] = true;
		$is_beside[6][28] = true;
		$is_beside[6][11] = true;
		$is_beside[6][10] = true;
		$is_beside[6][9]  = true;
		$is_beside[6][30] = true;
		$is_beside[6][25] = true;
		$is_beside[6][5]  = true;

		$is_beside[31][1]  = true;
		$is_beside[31][11] = true;
		$is_beside[31][8]  = true;
		$is_beside[31][13] = true;

		$is_beside[27][19] = true;
		$is_beside[27][20] = true;
		$is_beside[27][4]  = true;

		$is_beside[21][28] = true;
		$is_beside[21][4]  = true;
		$is_beside[21][5]  = true;
		$is_beside[21][23] = true;

		$is_beside[1][31] = true;
		$is_beside[1][11] = true;
		$is_beside[1][10] = true;
		$is_beside[1][13] = true;
		$is_beside[1][9]  = true;

		$is_beside[24][28] = true;
		$is_beside[24][4]  = true;
		$is_beside[24][20] = true;
		$is_beside[24][6]  = true;

		$is_beside[30][26] = true;
		$is_beside[30][22] = true;
		$is_beside[30][25] = true;
		$is_beside[30][6]  = true;
		$is_beside[30][9]  = true;
		$is_beside[30][7]  = true;

		$is_beside[7][30] = true;
		$is_beside[7][29] = true;
		$is_beside[7][9]  = true;

		$is_beside[29][7]  = true;
		$is_beside[29][14] = true;
		$is_beside[29][9]  = true;

		$is_beside[4][27] = true;
		$is_beside[4][21] = true;
		$is_beside[4][20] = true;
		$is_beside[4][28] = true;
		$is_beside[4][24] = true;

		$is_beside[12][2]  = true;
		$is_beside[12][15] = true;
		$is_beside[12][3]  = true;
		$is_beside[12][16] = true;
		$is_beside[12][18] = true;
		$is_beside[12][17] = true;
		$is_beside[12][8]  = true;

		$is_beside[9][13] = true;
		$is_beside[9][1]  = true;
		$is_beside[9][10] = true;
		$is_beside[9][6]  = true;
		$is_beside[9][29] = true;
		$is_beside[9][7]  = true;
		$is_beside[9][30] = true;

		$is_beside[26][30] = true;
		$is_beside[26][22] = true;
		$is_beside[26][23] = true;

		$is_beside[5][6]  = true;
		$is_beside[5][25] = true;
		$is_beside[5][21] = true;
		$is_beside[5][23] = true;
		$is_beside[5][28] = true;
		$is_beside[5][22] = true;

		$is_beside[8][12] = true;
		$is_beside[8][17] = true;
		$is_beside[8][11] = true;
		$is_beside[8][31] = true;
		$is_beside[8][13] = true;
		$is_beside[8][2]  = true;

		$is_beside[10][1]  = true;
		$is_beside[10][11] = true;
		$is_beside[10][9]  = true;
		$is_beside[10][6]  = true;

		$is_beside[18][16] = true;
		$is_beside[18][19] = true;
		$is_beside[18][17] = true;
		$is_beside[18][12] = true;

		$is_beside[22][25] = true;
		$is_beside[22][5]  = true;
		$is_beside[22][23] = true;
		$is_beside[22][26] = true;
		$is_beside[22][30] = true;

		$is_beside[19][18] = true;
		$is_beside[19][17] = true;
		$is_beside[19][20] = true;
		$is_beside[19][27] = true;

		$is_beside[28][24] = true;
		$is_beside[28][4]  = true;
		$is_beside[28][21] = true;
		$is_beside[28][5]  = true;
		$is_beside[28][6]  = true;

		$is_beside[14][13] = true;
		$is_beside[14][29] = true;
		$is_beside[14][9]  = true;

		$is_beside[2][13] = true;
		$is_beside[2][15] = true;
		$is_beside[2][12] = true;
		$is_beside[2][8]  = true;

		$is_beside[20][27] = true;
		$is_beside[20][19] = true;
		$is_beside[20][17] = true;
		$is_beside[20][11] = true;
		$is_beside[20][6]  = true;
		$is_beside[20][24] = true;
		$is_beside[20][4]  = true;

		$is_beside[13][14] = true;
		$is_beside[13][9]  = true;
		$is_beside[13][1]  = true;
		$is_beside[13][31] = true;
		$is_beside[13][6]  = true;
		$is_beside[13][8]  = true;
		$is_beside[13][2]  = true;

		$is_beside[11][6]  = true;
		$is_beside[11][10] = true;
		$is_beside[11][1]  = true;
		$is_beside[11][31] = true;
		$is_beside[11][20] = true;
		$is_beside[11][8]  = true;
		$is_beside[11][17] = true;

		$is_beside[23][21] = true;
		$is_beside[23][5]  = true;
		$is_beside[23][22] = true;
		$is_beside[23][26] = true;

		$is_beside[17][19] = true;
		$is_beside[17][20] = true;
		$is_beside[17][18] = true;
		$is_beside[17][11] = true;
		$is_beside[17][8]  = true;
		$is_beside[17][12] = true;

		$is_beside[25][5]  = true;
		$is_beside[25][22] = true;
		$is_beside[25][30] = true;

		return isset( $is_beside[ $source ][ $destination ] ) && $is_beside[ $source ][ $destination ] === true ? 'beside' : 'out';
	}

	public static function shop() {

		$shop = get_transient( 'zash_zagma_shop' );

		if ( $shop === false || count( (array) $shop ) == 0 ) {
			$shop = self::request( 'shopdetiles', [
				'username' => ZASH()->get_option( 'zagma.username' ),
				'password' => ZASH()->get_option( 'zagma.password' ),
				'shopcode' => ZASH()->get_option( 'zagma.shop_id' ),
			] );
			
			if ( is_wp_error( $shop ) ) {
				return get_option( 'zash_zagma_shop' );
			}

			set_transient( 'zash_zagma_shop', $shop, DAY_IN_SECONDS );
			update_option( 'zash_zagma_shop', $shop );
		}

		return $shop;
	}

	public static function get_cart_weight() {

		$weight = ZASH()->get_option( 'zagma.package_weight', 500 );

		foreach ( WC()->cart->get_cart() as $cart_item ) {

			if ( $cart_item['data']->is_virtual() ) {
				continue;
			}

			if ( $cart_item['data']->has_weight() ) {
				$weight += wc_get_weight( $cart_item['data']->get_weight() * $cart_item['quantity'], 'g' );
			} else {
				$weight += floatval( ZASH()->get_option( 'zagma.product_weight', 500 ) ) * $cart_item['quantity'];
			}
		}

		return $weight;
	}


}