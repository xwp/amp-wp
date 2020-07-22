<?php
/**
 * Class AMP_Facebook_Embed_Test
 *
 * @package AMP
 */

/**
 * Test AMP_Facebook_Embed_Test
 *
 * @covers AMP_Facebook_Embed_Handler
 */
class AMP_Facebook_Embed_Test extends WP_UnitTestCase {

	/**
	 * Data provider for test__conversion.
	 *
	 * @return array Data.
	 */
	public function get_conversion_data() {
		return [
			'no_embed'         => [
				'<p>Hello world.</p>',
				'<p>Hello world.</p>' . PHP_EOL,
			],
			'simple_url_https' => [
				'https://www.facebook.com/zuck/posts/10102593740125791' . PHP_EOL,
				'<p><amp-facebook data-href="https://www.facebook.com/zuck/posts/10102593740125791" layout="responsive" width="600" height="400"></amp-facebook></p>' . PHP_EOL,
			],
			'simple_url_http'  => [
				'http://www.facebook.com/zuck/posts/10102593740125791' . PHP_EOL,
				'<p><amp-facebook data-href="http://www.facebook.com/zuck/posts/10102593740125791" layout="responsive" width="600" height="400"></amp-facebook></p>' . PHP_EOL,
			],
			'no_dubdubdub'     => [
				'https://facebook.com/zuck/posts/10102593740125791' . PHP_EOL,
				'<p><amp-facebook data-href="https://facebook.com/zuck/posts/10102593740125791" layout="responsive" width="600" height="400"></amp-facebook></p>' . PHP_EOL,
			],
			'notes_url'        => [
				'https://www.facebook.com/notes/facebook-engineering/under-the-hood-the-javascript-sdk-truly-asynchronous-loading/10151176218703920/' . PHP_EOL,
				'<p><amp-facebook data-href="https://www.facebook.com/notes/facebook-engineering/under-the-hood-the-javascript-sdk-truly-asynchronous-loading/10151176218703920/" layout="responsive" width="600" height="400"></amp-facebook></p>' . PHP_EOL,
			],
			'photo_url'        => [
				'https://www.facebook.com/photo.php?fbid=10102533316889441&set=a.529237706231.2034669.4&type=3&theater' . PHP_EOL,
				'<p><amp-facebook data-href="https://www.facebook.com/photo.php?fbid=10102533316889441&amp;set=a.529237706231.2034669.4&amp;type=3&amp;theater" layout="responsive" width="600" height="400"></amp-facebook></p>' . PHP_EOL,
			],
			'notes_url2'       => [
				'https://www.facebook.com/zuck/videos/10102509264909801/' . PHP_EOL,
				'<p><amp-facebook data-href="https://www.facebook.com/zuck/videos/10102509264909801/" layout="responsive" width="600" height="400"></amp-facebook></p>' . PHP_EOL,
			],

		];
	}

	/**
	 * Test conversion.
	 *
	 * @dataProvider get_conversion_data
	 * @param string $source   Source.
	 * @param string $expected Expected.
	 */
	public function test__conversion( $source, $expected ) {
		$embed = new AMP_Facebook_Embed_Handler();
		$embed->register_embed();
		$filtered_content = apply_filters( 'the_content', $source );

		$this->assertEqualMarkup( $expected, $filtered_content );
	}

	/**
	 * Get scripts data.
	 *
	 * @return array Scripts.
	 */
	public function get_scripts_data() {
		return [
			'not_converted' => [
				'<p>Hello World.</p>',
				[],
			],
			'converted'     => [
				'https://www.facebook.com/zuck/posts/10102593740125791' . PHP_EOL,
				[ 'amp-facebook' => true ],
			],
		];
	}

	/**
	 * Test get_scripts().
	 *
	 * @dataProvider get_scripts_data
	 * @param string $source Source.
	 * @param array  $expected Expected scripts.
	 */
	public function test__get_scripts( $source, $expected ) {
		$embed = new AMP_Facebook_Embed_Handler();
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

	/**
	 * Data for test__raw_embed_sanitizer.
	 *
	 * @return array
	 */
	public function get_raw_embed_dataset() {
		return [
			'no_embed_blockquote'   => [
				'<p>Hello world.</p>',
				'<p>Hello world.</p>',
			],
			'div_without_instagram' => [
				'<div>lorem ipsum</div>',
				'<div>lorem ipsum</div>',
			],

			'post_embed'            => [
				'<div class="fb-post" data-href="https://www.facebook.com/notes/facebook-engineering/under-the-hood-the-javascript-sdk-truly-asynchronous-loading/10151176218703920/"></div>',
				'<amp-facebook width="600" height="400" data-href="https://www.facebook.com/notes/facebook-engineering/under-the-hood-the-javascript-sdk-truly-asynchronous-loading/10151176218703920/" data-embed-as="post" layout="responsive"></amp-facebook>',
			],

			'post_with_fallbacks'   => [
				'
					<div class="fb-post" data-href="https://www.facebook.com/20531316728/posts/10154009990506729/" data-width="500" data-show-text="true">
						<blockquote cite="https://developers.facebook.com/20531316728/posts/10154009990506729/" class="fb-xfbml-parse-ignore">
							Posted by <a href="https://www.facebook.com/facebook/">Facebook</a> on <a href="https://developers.facebook.com/20531316728/posts/10154009990506729/">Thursday, August 27, 2015</a>
						</blockquote>
					</div>
				',
				'
					<amp-facebook width="500" height="400" data-href="https://www.facebook.com/20531316728/posts/10154009990506729/"  data-show-text="true" data-embed-as="post" layout="responsive">
						<blockquote cite="https://developers.facebook.com/20531316728/posts/10154009990506729/" class="fb-xfbml-parse-ignore" fallback="">
							Posted by <a href="https://www.facebook.com/facebook/">Facebook</a> on <a href="https://developers.facebook.com/20531316728/posts/10154009990506729/">Thursday, August 27, 2015</a>
						</blockquote>
					</amp-facebook>
				',
			],

			'video_embed'           => [
				'<div class="fb-video" data-href="https://www.facebook.com/amanda.orr.56/videos/10212156330049017/" data-show-text="false"></div>',
				'<amp-facebook width="600" height="400" data-href="https://www.facebook.com/amanda.orr.56/videos/10212156330049017/" data-show-text="false" data-embed-as="video" layout="responsive"></amp-facebook>',
			],

			'page_embed'            => [
				'
					<div class="fb-page" data-href="https://www.facebook.com/xwp.co/" data-width="340" data-height="432" data-hide-cover="true" data-show-facepile="true" data-show-posts="false">
						<div class="fb-xfbml-parse-ignore">
							<blockquote cite="https://www.facebook.com/xwp.co/"><a href="https://www.facebook.com/xwp.co/">Like Us</a></blockquote>
						</div>
					</div>
				',
				'
					<amp-facebook-page width="340" height="432" data-href="https://www.facebook.com/xwp.co/" data-hide-cover="true" data-show-facepile="true" data-show-posts="false" layout="responsive">
						<div class="fb-xfbml-parse-ignore" fallback="">
							<blockquote cite="https://www.facebook.com/xwp.co/"><a href="https://www.facebook.com/xwp.co/">Like Us</a></blockquote>
						</div>
					</amp-facebook-page>
				',
			],

			'like'                  => [
				'
					<div class="fb-like" data-href="https://developers.facebook.com/docs/plugins/" data-width="400" data-layout="standard" data-action="like" data-size="small" data-show-faces="true" data-share="true"></div>
				',
				'
					<amp-facebook-like width="400" height="400" data-href="https://developers.facebook.com/docs/plugins/" data-layout="standard" data-action="like" data-size="small" data-show-faces="true" data-share="true" layout="responsive">
					</amp-facebook-like>
				',
			],

			'comments'              => [
				'
					<div class="fb-comments" data-href="https://developers.facebook.com/docs/plugins/comments#configurator" data-numposts="5"></div>
				',
				'<amp-facebook-comments width="600" height="400" data-href="https://developers.facebook.com/docs/plugins/comments#configurator" data-numposts="5" layout="responsive"></amp-facebook-comments>',
			],

			'comments_full_width'   => [
				'
					<div class="fb-comments" data-href="https://developers.facebook.com/docs/plugins/comments#configurator" data-width="100%" data-numposts="5"></div>
				',
				'<amp-facebook-comments width="auto" height="400" data-href="https://developers.facebook.com/docs/plugins/comments#configurator" data-numposts="5" layout="fixed-height"></amp-facebook-comments>',
			],

			'comments_full_width_2' => [
				'
					<div class="fb-comments" data-href="https://developers.facebook.com/docs/plugins/comments#configurator" data-height="123" data-width="100%" data-numposts="5"></div>
				',
				'<amp-facebook-comments width="auto" height="123" data-href="https://developers.facebook.com/docs/plugins/comments#configurator" data-numposts="5" layout="fixed-height"></amp-facebook-comments>',
			],

			'comment_embed'         => [
				'
					<div class="fb-comment-embed" data-href="https://www.facebook.com/zuck/posts/10102735452532991?comment_id=1070233703036185" data-width="500"></div>
				',
				'<amp-facebook width="500" height="400" data-href="https://www.facebook.com/zuck/posts/10102735452532991?comment_id=1070233703036185" data-embed-as="comment" layout="responsive"></amp-facebook>',
			],

			'remove_fb_root'        => [
				'<div id="fb-root"></div><script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.2"></script>', // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
				'',
			],
		];
	}

	/**
	 * Test raw_embed_sanitizer.
	 *
	 * @param string $source  Content.
	 * @param string $expected Expected content.
	 * @dataProvider get_raw_embed_dataset
	 * @covers AMP_Facebook_Embed_Handler::sanitize_raw_embeds()
	 */
	public function test__raw_embed_sanitizer( $source, $expected ) {
		$dom   = AMP_DOM_Utils::get_dom_from_content( $source );
		$embed = new AMP_Facebook_Embed_Handler();

		$embed->sanitize_raw_embeds( $dom );

		$layout_sanitizer = new AMP_Layout_Sanitizer( $dom );
		$layout_sanitizer->sanitize();

		$content = AMP_DOM_Utils::get_content_from_dom( $dom );

		$this->assertEqualMarkup( $expected, $content );
	}

	/**
	 * Assert markup is equal.
	 *
	 * @param string $expected Expected markup.
	 * @param string $actual   Actual markup.
	 */
	public function assertEqualMarkup( $expected, $actual ) {
		$actual   = preg_replace( '/\s+/', ' ', $actual );
		$expected = preg_replace( '/\s+/', ' ', $expected );
		$actual   = preg_replace( '/(?<=>)\s+(?=<)/', '', trim( $actual ) );
		$expected = preg_replace( '/(?<=>)\s+(?=<)/', '', trim( $expected ) );

		$this->assertEquals(
			array_filter( preg_split( '#(<[^>]+>|[^<>]+)#', $expected, -1, PREG_SPLIT_DELIM_CAPTURE ) ),
			array_filter( preg_split( '#(<[^>]+>|[^<>]+)#', $actual, -1, PREG_SPLIT_DELIM_CAPTURE ) )
		);
	}
}
