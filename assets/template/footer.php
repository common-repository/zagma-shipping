</div>
</body>
</html>
<?php 

function my_enqueue() {
    if ( ! wp_script_is( 'jquery', 'done' ) ) {
        wp_enqueue_script( 'jquery' );
    }
    wp_register_script( 'barcode', plugin_dir_url( ZASH_FILE ) . 'assets/js/jquery-barcode-2.0.2.min.js', [ 'jquery' ], '2.0.2' );
    wp_enqueue_script( 'barcode' );
    wp_add_inline_script( 'barcode', '$(".post_barcode span").each(function (index) {
        let post_barcode = $(this).html();
        $(this).barcode(post_barcode.trim(), "code128", {barWidth: 1, barHeight: 30});
    }); print();' );
    wp_register_style( 'css1', WOOI_PLUGIN_URL. 'assets/css/1.css' );
    wp_enqueue_style( 'css1' );

}
add_action('wp_enqueue_scripts', 'my_enqueue');

?>