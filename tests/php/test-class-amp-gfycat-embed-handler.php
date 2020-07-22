<?php
/**
 * Test Gfycat embed.
 *
 * @package AMP.
 */

/**
 * Class AMP_Gfycat_Embed_Test
 */
class AMP_Gfycat_Embed_Test extends WP_UnitTestCase {

	/**
	 * Set up.
	 *
	 * @global WP_Post $post
	 */
	public function setUp() {
		global $post;
		parent::setUp();

		// Mock the HTTP request.
		add_filter(
			'pre_oembed_result',
			static function( $pre, $url ) {
				if ( false === strpos( $url, 'tautwhoppingcougar' ) ) {
					return $pre;
				}
				return '<iframe src=\'https://gfycat.com/ifr/tautwhoppingcougar\' frameborder=\'0\' scrolling=\'no\' width=\'500\' height=\'281.25\'  allowfullscreen></iframe>';
			},
			10,
			2
		);

		/*
		 * As #34115 in 4.9 a post is not needed for context to run oEmbeds. Prior ot 4.9, the WP_Embed::shortcode()
		 * method would short-circuit when this is the case:
		 * https://github.com/WordPress/wordpress-develop/blob/4.8.4/src/wp-includes/class-wp-embed.php#L192-L193
		 * So on WP<4.9 we set a post global to ensure oEmbeds get processed.
		 */
		if ( version_compare( strtok( get_bloginfo( 'version' ), '-' ), '4.9', '<' ) ) {
			$post = self::factory()->post->create_and_get();
		}
	}

	/**
	 * Get conversion data.
	 *
	 * @return array
	 */
	public function get_conversion_data() {
		return [
			'no_embed'        => [
				'<p>Hello world.</p>',
				'<p>Hello world.</p>' . PHP_EOL,
			],

			'url_simple'      => [
				'https://gfycat.com/tautwhoppingcougar' . PHP_EOL,
				'<p><amp-gfycat width="500" height="281" data-gfyid="tautwhoppingcougar"></amp-gfycat></p>' . PHP_EOL,
			],

			'url_with_detail' => [
				'https://gfycat.com/gifs/detail/tautwhoppingcougar' . PHP_EOL,
				'<p><amp-gfycat width="500" height="281" data-gfyid="tautwhoppingcougar"></amp-gfycat></p>' . PHP_EOL,
			],

			'url_with_params' => [
				'https://gfycat.com/gifs/detail/tautwhoppingcougar?foo=bar' . PHP_EOL,
				'<p><amp-gfycat width="500" height="281" data-gfyid="tautwhoppingcougar"></amp-gfycat></p>' . PHP_EOL,
			],

		];
	}

	/**
	 * Test conversion.
	 *
	 * @param string $source Source.
	 * @param string $expected Expected.
	 * @dataProvider get_conversion_data
	 */
	public function test__conversion( $source, $expected ) {
		$embed = new AMP_Gfycat_Embed_Handler();
		$embed->register_embed();
		$filtered_content = apply_filters( 'the_content', $source );

		$this->assertEquals( $expected, $filtered_content );
	}

	/**
	 * Get scripts data.
	 *
	 * @return array
	 */
	public function get_scripts_data() {
		return [
			'not_converted' => [
				'<p>Hello World.</p>',
				[],
			],
			'converted'     => [
				'https://www.gfycat.com/gifs/detail/tautwhoppingcougar' . PHP_EOL,
				[ 'amp-gfycat' => true ],
			],
		];
	}

	/**
	 * Test scripts.
	 *
	 * @param string $source Source.
	 * @param string $expected Expected.
	 * @dataProvider get_scripts_data
	 */
	public function test__get_scripts( $source, $expected ) {
		$embed = new AMP_Gfycat_Embed_Handler();
		$embed->register_embed();
		$source = apply_filters( 'the_content', $source );

		$whitelist_sanitizer = new AMP_Tag_And_Attribute_Sanitizer( AMP_DOM_Utils::get_dom_from_content( $source ) );
		$whitelist_sanitizer->sanitize();

		$scripts = array_merge(
			$embed->get_scripts(),
			$whitelist_sanitizer->get_scripts()
		);

		$this->assertEquals( $expected, $scripts );
	}
}
