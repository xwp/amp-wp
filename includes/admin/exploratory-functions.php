<?php
add_filter( 'amp_post_template_file', 'xyz_amp_set_custom_template', 10, 3 );
function xyz_amp_set_custom_template( $file, $type, $post ) {

	if ( get_theme_mod('amp_mode') ) {
		switch ($type) {
			case 'single':
				$file = AMP__DIR__ . '/templates/single-amedina.php';
				break;
			case 'style':
				$file = AMP__DIR__ . '/templates/style-amedina.php';
				break;
		}
	} else {
		switch ($type) {
			case 'single':
				$file = AMP__DIR__ . '/templates/single.php';
				break;
			case 'style':
				$file = AMP__DIR__ . '/templates/style.php';
				break;
		}
	}
	return $file;
}