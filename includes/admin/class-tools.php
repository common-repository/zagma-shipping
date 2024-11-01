<?php
namespace ZagmaShipping\includes\admin;

class ZASH_Settings_Tools extends ZASH_Settings {

	protected static $_instance = null;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function get_sections() {
		return [
			[
				'id'    => 'zash_tools',
				'title' => 'ابزارهای کاربردی',
			]
		];
	}

	public function get_fields() {
		return [
			'zash_tools' => [
				
				[
					'label'   => 'وضعیت سفارشات کمکی',
					'name'    => 'status_enable',
					'default' => '0',
					'type'    => 'checkbox',
					'css'     => 'width: 350px;',
					'desc'    => 'جهت مدیریت بهتر سفارشات فروشگاه، وضعیت های زیر به پنل اضافه خواهد شد.
					<ol>
						<li>ارسال شده به انبار</li>
						<li>بسته بندی شده</li>
						<li>تحویل پیک</li>
					</ol>
					',
				],
				[
					'label'   => 'فقط روش ارسال رایگان',
					'name'    => 'hide_when_free',
					'default' => '0',
					'type'    => 'checkbox',
					'css'     => 'width: 350px;',
					'desc'    => 'در صورتی که یک روش ارسال رایگان در دسترس باشد، بقیه روش های ارسال مخفی می شوند.',
				],
				[
					'label'       => 'نوع ارسال در حالت رایگان',
					'name'    	  => 'free_send_type',
					'type'        => 'select',
					'options'     => [ 1 => 'سفارشی' , 2 => 'پیشتاز'],
					'default'     => 1,
				],
				[
					'label'       => 'بارگذاری لیست شهر ها',
					'name'    	  => 'load_city_list',
					'type'        => 'select',
					'options'     => [ 1 => 'از پلاگین' , 2 => 'از سرور زاگما'],
					'default'     => 1,
				],
				[
					'label'       => 'اتصال به سرور',
					'name'    	  => 'zagma_server',
					'type'        => 'select',
					'options'     => [ 1 => 'سرور ایران' , 2 => 'سرور کلود'],
					'default'     => 1,
				]
			]
		];
	}

	public static function output() {

		$instance = self::instance();

		echo '<div class="wrap">';

		$instance->show_navigation();
		$instance->show_forms();

		echo '</div>';
	}
}