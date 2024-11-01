<?php
namespace ZagmaShipping\includes;
use function ZagmaShipping\ZASH;



if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class ZASH_Status {

	public static $status = [

		17 => 'wc-zash-shipping',
		20 => 'wc-zash-shipping',
		22 => 'wc-zash-shipping',

		1 => 'wc-completed',
		2 => 'wc-completed',
		3 => 'wc-completed',
		23 => 'wc-completed',
		24 => 'wc-completed',
		32 => 'wc-completed',
		33 => 'wc-completed',

		27 => 'wc-zash-returned',
		28 => 'wc-zash-returned',
		29 => 'wc-zash-returned',
		
		18 => 'wc-zash-unacpt',
		19 => 'wc-zash-unacpt',
		21 => 'wc-zash-unacpt',
		26 => 'wc-zash-unacpt',
		30 => 'wc-zash-unacpt',
		31 => 'wc-zash-unacpt',

		25 => 'wc-zash-pw',

		4 => 'wc-zash-deleted',
		36 => 'wc-zash-deleted',

		6  => 'wc-zash-suspend',
		7  => 'wc-zash-suspend',
		8  => 'wc-zash-suspend',
		9  => 'wc-zash-suspend',
		10  => 'wc-zash-suspend',
		11  => 'wc-zash-suspend',
		12  => 'wc-zash-suspend',
		13  => 'wc-zash-suspend',
		14  => 'wc-zash-suspend',
		15  => 'wc-zash-suspend',
		16  => 'wc-zash-suspend',


		34 => 'wc-zash-readyto-ship',

		5 => 'wc-zash-packaged',		
		
	];

	public function __construct() {
		add_action( 'init', [ $this, 'register_order_statuses' ] );
		add_filter( 'wc_order_statuses', [ $this, 'add_order_statuses' ], 10, 1 );
		add_filter( 'woocommerce_reports_order_statuses', [ $this, 'reports_statuses' ], 10, 1 );
		add_filter( 'woocommerce_order_is_paid_statuses', [ $this, 'paid_statuses' ], 10, 1 );
		add_filter( 'bulk_actions-edit-shop_order', [ $this, 'bulk_actions' ], 20, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		
		add_action( 'add_meta_boxes', [ $this, 'order_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_order_meta_box' ], 1000, 3 );
		add_action( 'manage_posts_extra_tablenav', [ $this, 'top_order_list' ], 20, 1 );
		add_action( 'wp_ajax_zash_change_order_status', [ $this, 'change_status_callback' ] );
		add_action( 'wp', [ $this, 'check_status_scheduled' ] );
		add_action( 'zash_check_status', [ $this, 'check_status_callback' ] );
		
	}

	public function get_statues() {

		$statuses = [];

		if ( ZASH_Zagma::is_enable() ) {
			$statuses['wc-zash-shipping']   = 'درحال توزیع';
			$statuses['wc-zash-returned']      = 'برگشتی';
			$statuses['wc-zash-unacpt']   = 'غیر قابل توضیع';
			$statuses['wc-zash-pw']   = 'باجه معطله';
			$statuses['wc-zash-deleted']       = 'انصرافی';
			$statuses['wc-zash-suspend']   = 'معلق - پستی';
			$statuses['wc-zash-readyto-ship']   = 'تایید شده در انتظار مامور پست';
			$statuses['wc-zash-packaged']   = 'نیازمند تائید فروشگاه دار';
		}

		return $statuses;
	}

	public function register_order_statuses() {

		foreach ( $this->get_statues() as $status => $label ) {
			register_post_status( $status, [
				'label'                     => $label,
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ),
			] );
		}

	}

	public function add_order_statuses( $order_statuses ) {
		$new_order_statuses = [];

		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;

			if ( 'wc-processing' === $key ) {

				foreach ( $this->get_statues() as $status => $label ) {
					$new_order_statuses[ $status ] = $label;
				}

			}
		}

		return $new_order_statuses;
	}

	public function reports_statuses( $order_status ) {

		$dont_report = [
			'wc-zash-returned',
			'wc-zash-deleted',
		];

		foreach ( $this->get_statues() as $status => $label ) {
			if ( ! in_array( $status, $dont_report ) ) {
				$order_status[] = str_replace( 'wc-', '', $status );
			}
		}

		return $order_status;
	}

	public function paid_statuses( $order_status ) {

		$dont_paid = [
			'wc-zash-returned',
			'wc-zash-deleted',
		];

		foreach ( $this->get_statues() as $status => $label ) {
			if ( ! in_array( $status, $dont_paid ) ) {
				$order_status[] = str_replace( 'wc-', '', $status );
			}
		}

		return $order_status;
	}

	public function bulk_actions( $actions ) {

		foreach ( $this->get_statues() as $status => $label ) {
			$key                       = str_replace( 'wc-', '', $status );
			$actions[ 'mark_' . $key ] = 'تغییر وضعیت به ' . $label;
		}

		return $actions;
	}

	public function enqueue_scripts() {

		wp_enqueue_style( 'zash_order_status', ZASH_URL . 'assets/css/status.css' );


		$screen = get_current_screen();

		if ( $screen->id == 'edit-shop_order' ) {
			wp_enqueue_script( 'zash_zagma_list', ZASH_URL . 'assets/js/zagma-list.js' );
		}

		if ( $screen->id == 'shop_order' ) {
			wp_enqueue_script( 'zash_zagma_list', ZASH_URL . 'assets/js/zagma-order.js' );
		}
	}

	public function top_order_list( $which ) {
		global $typenow;

		if ( 'shop_order' === $typenow && 'top' === $which ) {
			?>
            <div class="alignleft actions custom">
                <button type="button" id="zash-zagma-submit" class="button-primary"
                        title="جهت ثبت سفارشات انتخاب شده در پنل زاگما و دریافت بارکد پستی، کلیک کنید.">ثبت در زاگما
                </button>
                <button type="button" id="zash-zagma-ship" class="button-primary"
                        title="پس از ثبت سفارش در پنل، جهت اعلام به پست برای جمع آوری بسته اینجا کلیک کنید.">آماده ارسال
                </button>
            </div>
			<?php
		}
	}

	public function order_meta_box() {
		add_meta_box( 'zagma_order', 'زاگما', [ $this, 'order_meta_box_callback' ], 'shop_order', 'side' );
	}

	public function order_meta_box_callback( $post, $args ) {

		/** @var WC_Order $order */
		$order = wc_get_order( $post->ID );

		$order_uuid   = $order->get_meta( 'zagma_order_uuid' );
		$zagma_weight = ZASH_Order::get_order_weight( $order );

		$shipping_state = $order->get_meta( '_shipping_state_id' );
		$shipping_city  = $order->get_meta( '_shipping_city_id' );

		$shipping_method = ZASH_Order::get_shipping_method( $order )

		?>

		<?php if ( empty( $order_uuid ) ) { ?>

            <p class="form-field-wide">
                <label for="zagma_weight">وزن سفارش:</label>
                <input type="number" name="zagma_weight" id="zagma_weight" style="width: 100%"
                       value="<?php echo $zagma_weight; ?>">
            </p>

            <p class="form-field-wide">
                <label for="shipping_state_city">استان/شهر مقصد:</label>
                <select name="shipping_state_city" id="shipping_state_city" style="width: 100%">
					<?php

					foreach ( ZASH()::states() as $state_key => $state ) {
						foreach ( ZASH()::cities( $state_key ) as $city_key => $city ) {
							$key      = "{$state_key}-{$city_key}";
							$selected = selected( $key, "{$shipping_state}-{$shipping_city}", false );
							printf( "<option value='%s' %s>%s - %s</option>", $key, $selected, $state, $city );
						}
					}

					?>
                </select>
            </p>

            <p style="display: none;" id="zash-zagma-submit-tip">لطفا ابتدا روی بروزرسانی کلیک نمایید.</p>

            <button type="button" id="zash-zagma-submit" class="button-primary"
                    title="جهت ثبت سفارشات انتخاب شده در پنل زاگما و دریافت بارکد پستی، کلیک کنید.">ثبت در زاگما
            </button>
		<?php } else { ?>

            <p class="form-field-wide">
                <label>وزن سفارش:</label>
                <input type="number" style="width: 100%"
                       value="<?php echo $zagma_weight; ?>" disabled="disabled">
            </p>

            <p class="form-field-wide">
                <label>نوع پست:</label>
                <select style="width: 100%" disabled="disabled">
                    <option value="" <?php selected( null, $shipping_method ); ?>>غیرپستی</option>
                    <option value="0" <?php selected( 0, $shipping_method ); ?>>پست سفارشی</option>
                    <option value="1" <?php selected( 1, $shipping_method ); ?>>پست پیشتاز</option>
                </select>
            </p>

            <button type="button" id="zash-zagma-ship" class="button-primary"
                    title="پس از ثبت سفارش در پنل، جهت اعلام به پست برای جمع آوری بسته اینجا کلیک کنید.">آماده ارسال
            </button>
			<?php
		}

		?>
        <div class="zash-tips" style="margin-top: 15px;"></div>
		<?php
	}

	public function save_order_meta_box( $order_id, $post, $update ) {

		$order_uuid = get_post_meta( $order_id, 'zagma_order_uuid', true );

		if ( ! empty( $order_uuid ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( get_post_status( $order_id ) === 'auto-draft' ) {
			return;
		}

		if ( ! isset( $_POST['zagma_weight'], $_POST['shipping_state_city'] ) ) {
			return;
		}

		update_post_meta( $order_id, 'zagma_weight', intval( $_POST['zagma_weight'] ) );

		list( $state_id, $city_id ) = explode( '-',sanitize_text_field($_POST['shipping_state_city']) );

		$state = ZASH()::get_state( $state_id );
		$city  = ZASH()::get_city( $city_id );

		if ( ! is_null( $state ) && ! is_null( $city ) ) {
			update_post_meta( $order_id, '_shipping_state', $state );
			update_post_meta( $order_id, '_shipping_state_id', $state_id );
			update_post_meta( $order_id, '_shipping_city', $city );
			update_post_meta( $order_id, '_shipping_city_id', $city_id );
		}
	}

	public function change_status_callback() {

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( - 1 );
		}

		$status = sanitize_text_field($_POST['status']) ?? null;
		
		if ( ! wc_is_order_status( 'wc-' . $status ) ) {

			echo json_encode( [
				'success' => false,
				'message' => 'وضعیت انتخاب شده معتبر نمی باشد.',
			] );

			die();
		}

		$order_id = sanitize_text_field($_POST['id']) ?? null;

		if ( is_null( $order_id ) || ! is_numeric( $order_id ) ) {

			echo json_encode( [
				'success' => false,
				'message' => 'سفارش انتخاب شده معتبر نمی باشد.',
			] );

			die();
		}

		/** @var WC_Order $order */
		$order = wc_get_order( intval( $order_id ) );
		
		if ( $order == false ) {

			echo json_encode( [
				'success' => false,
				'message' => 'سفارش انتخاب شده وجود ندارد.',
			] );

			die();
		}

		$zagma_post_type = ZASH_Order::get_shipping_method( $order );
		
		$zagma_pay_type_method = 3;
			if ( $zagma_post_type == 2 || ($zagma_post_type == 'free' && ZASH()->get_option( 'tools.free_send_type' ) == 2) ) {
				$zagma_pay_type_method = ZASH_Order::get_Pay_Type_Method( 'Zagma_Pishtaz_Method' );
			} else if ( $zagma_post_type == 1 || ($zagma_post_type == 'free' && ZASH()->get_option( 'tools.free_send_type' ) == 1)) {
				$zagma_pay_type_method = ZASH_Order::get_Pay_Type_Method( 'Zagma_Sefareshi_Method' );
			}
			
		//free_shipping
		if ( is_null( $zagma_post_type ) ) {

			echo json_encode( [
				'success' => false,
				'message' => 'روش ارسال این سفارش زاگما نیست.',
			] );

			die();
		}

		$zagma_order_uuid = get_post_meta( $order_id, 'zagma_order_uuid', true );
		
		if ( $status == 'zash-packaged' ) { // Submit & get post barcode

			if ( ! empty( $zagma_order_uuid ) ) {

				echo json_encode( [
					'success' => false,
					'message' => 'این سفارش قبلا در پنل ثبت شده است.',
				] );

				die();
			}

			$products = [];

			foreach ( $order->get_items() as $order_item ) {

				/** @var WC_Product $product */
				$product = $order_item->get_product();

				if ( $product && $product->is_virtual() ) {
					continue;
				}

				$price = $order_item->get_total() / $order_item->get_quantity();

				if ( get_woocommerce_currency() == 'IRR' ) {
					$price /= 10;
				}

				$title = $order_item->get_name();

				if ( function_exists( 'mb_substr' ) ) {
					$title = mb_substr( $title, 0, 50 );
				}
				$get_id = $product->get_id() ?? null;
				$vazn = $product->get_weight() ?? floatval( ZASH()->get_option( 'zagma.product_weight', 500 ) );
				$vazn = $order_item->get_quantity() * $vazn;
				$products[] = [
					'name'      => $title,
					'price'      => intval( $price ),
					'code' => $get_id,
					'quantity'   => $order_item->get_quantity(),
					'vazn'  => wc_get_weight($vazn, 'g'),
				];
			}

			$order_weight = ZASH_Order::get_order_weight( $order );

			$zagma_pay_type = 2;

			if ( $order->get_payment_method() == 'cod' ) {

				if ( $order->get_shipping_total() ) {
					$zagma_pay_type = 1;

					$packaging_cost = $order->get_meta( 'packaging_cost' );

					if ( $packaging_cost ) {

						if ( get_woocommerce_currency() == 'IRR' ) {
							$packaging_cost /= 10;
						}

						$products[] = [
							'name'      => 'بسته بندی',
							'price'      =>intval( $packaging_cost ),
							'code' => null,
							'quantity'      => 1,
							'vazn'     => 0,
						];
					}

				} else {
					$zagma_pay_type = 1;
				}

			}
			if( $zagma_post_type == 'free' ){
				$zagma_post_type = ZASH()->get_option( 'tools.free_send_type' );
				$zagma_pay_type_method = 1;
			}
			$order_total          = $order->get_total()  - $order->get_total_tax() - $order->get_total_shipping() - $order->get_shipping_tax();
			$data = apply_filters( 'zash_zagma_submit_order', [
				'username'        => ZASH()->get_option( 'zagma.username' ),
				'password'        => ZASH()->get_option( 'zagma.password' ),
				'shopkod'        => ZASH()->get_option( 'zagma.shop_id' ),
				'mobile'         => str_replace( [ '+98', '0098' ], '0', $order->get_billing_phone() ),
				'phone'          => null,
				'weight' => $order_weight,
				'pay'       => $zagma_pay_type,
				'noersal'     => $zagma_post_type,
				'ostan'  => $order->get_meta( '_shipping_state_id' ),
				'city'      => $order->get_meta( '_shipping_city_id' ),
				'price' => intval($order_total),
				'buyer'     => $order->get_shipping_first_name() . ' '.$order->get_shipping_last_name(),
				'adress'        => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
				'codepos'    => $order->get_shipping_postcode(),
				'email'          => null,
				'cart'       => $products,
				'noehazine'  => $zagma_pay_type_method,
				'id'    => $order_id,
			], $order );

			$response = ZASH_Zagma::request( 'orderwitharray', $data );

			if ( is_wp_error( $response ) || $response->vaz != 'true' ) {

				ZASH()->log( __METHOD__ . ' Line: ' . __LINE__ );
				ZASH()->log( $data );
				ZASH()->log( $response );

				

				echo json_encode( [
					'success' => false,
					'message' => $response->dalil,
				] );

				die();
			}

			update_post_meta( $order_id, 'zagma_order_uuid',$response->barcod );
			update_post_meta( $order_id, 'zagma_order_id', $response->barcod );
			update_post_meta( $order_id, 'zagma_send_price', $price );
			update_post_meta( $order_id, 'zagma_send_price_tax', 0 );
			update_post_meta( $order_id, 'zagma_send_time', time() );
			update_post_meta( $order_id, 'zagma_weight', $order_weight );
			update_post_meta( $order_id, 'post_barcode', $response->barcod );
            
			$note = "کد رهگیری مرسوله شما: {$response->barcod}
						می توانید مرسوله خود را از طریق لینک 
						https://www.zagma.ir/order-tracking/?id={$response->barcod}
						 رهگیری نمایید.";

			$order->set_status( $status );
			$order->save();
			$order->add_order_note( $note, 1 );

			do_action( 'zash_save_order_post_barcode', $order, $response->barcod );

			echo json_encode( [
				'success' => true,
				'message' => 'بسته بندی شده',
			] );

			die();

		} else if ( $status == 'zash-readyto-ship' ) {
			
			if ( empty( $zagma_order_uuid ) ) {

				echo json_encode( [
					'success' => false,
					'message' => 'سفارش در پنل ثبت نشده است.',
				] );

				die();
			}

			$zagma_order_id = get_post_meta( $order_id, 'zagma_order_id', true );
			
			$data = array (
				"username" => ZASH()->get_option( 'zagma.username' ),
				"password" => ZASH()->get_option( 'zagma.password' ),
				"shopkod" => ZASH()->get_option( 'zagma.shop_id' ),
				"code" => $zagma_order_id,
				"function" => 's', // s or r
		  	);

			$response = ZASH_Zagma::request( 'changestatusorder', $data );

			if ( is_wp_error( $response ) || $response->vaz != 'true' ) {

				ZASH()->log( __METHOD__ . ' Line: ' . __LINE__ );
				ZASH()->log( $data );
				ZASH()->log( $response );

				

				echo json_encode( [
					'success' => false,
					'message' => $response->dalil,
				] );

				die();
			}

			$order->set_status( $status );
			$order->save();

			echo json_encode( [
				'success' => true,
				'message' => 'آماده به ارسال',
			] );

			die();

		} else {

			echo json_encode( [
				'success' => false,
				'message' => "ابتدا باید به 'بسته بندی شده' تغییر وضعیت دهید.",
			] );

			die();

		}

	}

	public function check_status_scheduled() {
		if ( ! wp_next_scheduled( 'zash_check_status' ) ) {
			wp_schedule_event( time(), 'hourly', 'zash_check_status' );
		}
	}

	public function check_status_callback() {

		$args_query = [
			'post_type'   => [ 'shop_order' ],
			'post_status' => [
				self::$status[5],
				self::$status[34],
				self::$status[17],
				self::$status[20],
				self::$status[22],
				self::$status[25],
			],
			'nopaging'    => true,
			'order'       => 'DESC',
			'meta_key'    => 'zagma_order_uuid',
		];

		$query = new WP_Query( $args_query );
		
		$posts = array_column( $query->posts, 'ID' );
		
		$posts = array_map( function ( $post_id ) {
			return [
				'code'      => get_post_meta( $post_id, 'zagma_order_uuid', true ),
				'shopkod' => ZASH()->get_option( 'zagma.shop_id' ),
				'order_id' => $post_id
			];
		}, $posts );
		
		if ( count( $posts ) ) {

			$statuses = ZASH_Zagma::request( 'orderstatus', $posts);
			
			if ( is_wp_error( $statuses ) ) {
				return false;
			}
			
			$orders = array_combine($statuses->barcode,$statuses->zagma);
			
			foreach ( $posts as $post ) {

				if ( isset( $orders[ $post['code'] ] ) ) {

					$status = $orders[ $post['code'] ];

					$status = self::$status[ $status ] ?? null;
					
					if ( ! is_null( $status ) ) {

						$order = wc_get_order( $post['order_id'] );
						
						if ( $order->get_status() != $status ) {
							$order->set_status( $status, 'بروزرسانی خودکار زاگما -',true);
							$order->save();
							wp_update_post(array(
								'ID'    =>  $post['order_id'],
								'post_status'   =>  $status
								));
						}

					}
				}

			}

		}

		wp_reset_postdata();
	}

}

new ZASH_Status();
