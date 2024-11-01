<?php
namespace ZagmaShipping\includes\admin;

use ZagmaShipping\includes\ZASH_Zagma;

class ZASH_Settings_Zagma extends ZASH_Settings {

	protected static $_instance = null;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		add_action( 'admin_enqueue_scripts', [self::$_instance,'enqueueAdminStyles'] );
		add_filter( 'script_loader_tag', [self::$_instance , 'mind_defer_scripts'], 10, 3 );
		return self::$_instance;
	}

	public function get_sections() {
		return [
			[
				'id'    => 'zash_zagma',
				'title' => 'پیشخوان مجازی پست',
			],
		];
	}

	public function get_fields() {

		if ( ZASH_Zagma::is_enable() ) {
			$shop = get_transient( 'zash_zagma_shop' );

			if ( $shop === false || count( (array) $shop ) == 0 ) {
				ZASH_Zagma::shop();
				$shop = '<span style="color: red;">اطلاعات فروشگاه بارگذاری نشده است و ممکن است هزینه های ارسال بطور دقیق محاسبه نشود.</span>';
			} else {
				$shop = sprintf( '%s | %s %s ', $shop->shopname, $shop->statefa,$shop->cityfa );
			}
			$shop = 'اطلاعات فروشگاه: ' . $shop;

		} else {
			$shop = '';
		}

		return [
			'zash_zagma' => [
				
				[
					'label'   => 'فعالسازی زاگما',
					'name'    => 'enable',
					'default' => '0',
					'type'    => 'checkbox',
					'css'     => 'width: 350px;',
					'desc'    => 'فعالسازی پیشخوان مجازی زاگما',
				],
				[
					'label'   => 'نمایش اعتبار زاگما',
					'name'    => 'show_credit',
					'default' => '0',
					'type'    => 'checkbox',
					'css'     => 'width: 350px;',
					'desc'    => 'اعتبار پنل زاگما در منو بالا مدیریت نمایش داده می شود.',
				],
				[
					'label'   => 'رند کردن هزینه ارسال',
					'name'    => 'round_price',
					'default' => '0',
					'type'    => 'checkbox',
					'css'     => 'width: 350px;',
					'desc'    => 'درصورت فعالسازی هزینه ارسال به سمت بالا رند می شوند.',
				],
				[
					'label'   => 'وزن پیشفرض هر محصول',
					'name'    => 'product_weight',
					'default' => 500,
					'type'    => 'number',
					'css'     => 'width: 350px;',
					'desc'    => "در صورتی که برای محصول وزنی وارد نشده بود، بصورت پیشفرض وزن محصول چند گرم در نظر گرفته شود؟",
				],
				[
					'label'   => 'وزن بسته بندی',
					'name'    => 'package_weight',
					'default' => 500,
					'type'    => 'number',
					'css'     => 'width: 350px;',
					'desc'    => "بطور میانگین وزن بسته بندی ها چند گرم در نظر گرفته شود؟",
				],[
					'label'       => 'نوع اتصال',
					'name'    	  => 'connection',
					'type'        => 'select',
					'options'     => [ 1 => 'توکن' , 2 => 'نام کاربری و رمز'],
					'default'     => 2,
				],
				[
					'label'   => 'توکن',
					'name'    => 'token',
					'default' => '',
					'type'    => 'text',
					'css'     => 'width: 350px;',
					'desc'    => 'توکن را می توانید در زاگما قسمت مدیریت فروشگاه»مشخصات فروشگاه ، تب تنظیمات فروشگاه(وب سرویس) مشاهده نمایید و یا توکن جدید بسازید.',
				],
				[
					'label'   => 'نام کاربری',
					'name'    => 'username',
					'default' => '',
					'type'    => 'text',
					'css'     => 'width: 350px;',
					'desc'    => 'نام کاربری خود در سایت زاگما را وارد نمایید.',
				],
				[
					'label'   => 'رمزعبور',
					'name'    => 'password',
					'default' => '',
					'type'    => 'text',
					'css'     => 'width: 350px;',
					'desc'    => 'رمزعبور خود در سایت زاگما را وارد نمایید.',
				],
				[
					'label'   => 'شناسه فروشگاه',
					'name'    => 'shop_id',
					'default' => '',
					'type'    => 'text',
					'css'     => 'width: 350px;',
					'desc'    => 'شناسه فروشگاه را می توانید در زاگما قسمت مدیریت فروشگاه»مشخصات فروشگاه ، تب مشخصات فروشگاه قسمت کد فروشگاه(قرمز رنگ) مشاهده نمایید.<br>'.$shop,
				],
				[
					'name' => 'notes',
					'desc' => 'نکات:<ol>
<li>پست سفارشی و پیشتاز بسته با حداکثر وزن 30 کیلوگرم را می پذیرد.</li>
<li>بیمه غرامت پست برای محصولات با حداکثر ارزش 9 میلیون تومان پرداخت می شود.</li>
<li>به منظور تسهیل بهتر در ارتباط با زاگما حتما آی پی (ip = ('.$_SERVER['SERVER_ADDR'].') ) سیستم فروشگاهی خود را در قسمت مدیریت فروشگاه»مشخصات فروشگاه تب مشخصات سرور فروشگاه ثبت نمایید، شما می توانید تعداد نامحدود آی پی برای اتصال به زاگما ثبت نمایید.</li>
<li>برای حمل و نقل بهتر، حتما در قسمت مدیریت »مشخصات فروشگاه تب مشخصات فروشگاه محل خود را در نقشه مشخص نمایید. </li>
</ol>',
					'type' => 'html',
				],
			],
		];
	}

	public static function output() {

		$instance = self::instance();

		echo '<div class="wrap">';

		$instance->show_navigation();
		$instance->show_forms();

		echo '</div>';

	}
	public function enqueueAdminStyles(){ 
		$page = (empty($_GET['page']) ? '' : sanitize_text_field($_GET['page']));
		if($page == 'zash-zagma')
			wp_enqueue_script('zagma_js', plugins_url('admin.js',__FILE__ ));
	 } 
	 
	
	public function mind_defer_scripts( $tag, $handle, $src ) {
		if ( $handle == 'zagma_js' ) {
			
			return str_replace("script ","script defer='defer' ",$tag);
		}
		return $tag;
	} 
}



