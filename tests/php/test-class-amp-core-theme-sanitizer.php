<?php
/**
 * Class AMP_Core_Theme_Sanitizer_Test.
 *
 * @package AMP
 */

use AmpProject\AmpWP\Tests\Helpers\AssertContainsCompatibility;
use AmpProject\AmpWP\Tests\Helpers\LoadsCoreThemes;
use AmpProject\Dom\Document;
use AmpProject\AmpWP\Tests\Helpers\PrivateAccess;

/**
 * Class AMP_Core_Theme_Sanitizer_Test
 */
class AMP_Core_Theme_Sanitizer_Test extends WP_UnitTestCase {

	use AssertContainsCompatibility;
	use PrivateAccess;
	use LoadsCoreThemes;

	public function setUp() {
		parent::setUp();

		$this->register_core_themes();
	}

	public function tearDown() {
		parent::tearDown();

		$this->restore_theme_directories();
	}

	/**
	 * Data for testing the conversion of a CSS selector to a XPath.
	 *
	 * @return array
	 */
	public function get_xpath_from_css_selector_data() {
		return [
			// Single element.
			[ 'body', '//body' ],
			// Simple ID.
			[ '#some-id', "//*[ @id = 'some-id' ]" ],
			// Simple class.
			[ '.some-class', "//*[ @class and contains( concat( ' ', normalize-space( @class ), ' ' ), ' some-class ' ) ]" ],
			// Class descendants.
			[ '.some-class .other-class', "//*[ @class and contains( concat( ' ', normalize-space( @class ), ' ' ), ' some-class ' ) ]//*[ @class and contains( concat( ' ', normalize-space( @class ), ' ' ), ' other-class ' ) ]" ],
			// Class direct descendants.
			[ '.some-class > .other-class', "//*[ @class and contains( concat( ' ', normalize-space( @class ), ' ' ), ' some-class ' ) ]/*[ @class and contains( concat( ' ', normalize-space( @class ), ' ' ), ' other-class ' ) ]" ],
			// ID direct descendant elements.
			[ '#some-id > ul', "//*[ @id = 'some-id' ]/ul" ],
			// ID direct descendant elements with messy whitespace.
			[ "   \t  \n #some-id    \t  >   \n  ul  \t \n ", "//*[ @id = 'some-id' ]/ul" ],
		];
	}

	/**
	 * Test xpath_from_css_selector().
	 *
	 * @dataProvider get_xpath_from_css_selector_data
	 * @covers AMP_Core_Theme_Sanitizer::xpath_from_css_selector
	 *
	 * @param string $css_selector CSS Selector.
	 * @param string $expected     Expected XPath expression.
	 */
	public function test_xpath_from_css_selector( $css_selector, $expected ) {
		$dom       = new Document();
		$sanitizer = new AMP_Core_Theme_Sanitizer( $dom );
		$actual    = $this->call_private_method( $sanitizer, 'xpath_from_css_selector', [ $css_selector ] );
		$this->assertEquals( $expected, $actual );
	}

	public function get_get_closest_submenu_data() {
		$html = '
			<nav>
				<ul class="primary-menu">
					<li id="menu-item-1" class="menu-item menu-item-1"><a href="https://example.com/a">Link A</a></li>
					<li id="menu-item-2" class="menu-item menu-item-2"><a href="https://example.com/b">Link B</a><span class="icon"></span>
						<ul id="sub-menu-1" class="sub-menu">
							<li id="menu-item-3" class="menu-item menu-item-3"><a href="https://example.com/c">Link C</a></li>
							<li id="menu-item-4" class="menu-item menu-item-4"><a href="https://example.com/d">Link D</a></li>
						</ul>
					</li>
					<li id="menu-item-5" class="menu-item menu-item-5"><a href="https://example.com/e">Link E</a><span class="icon"></span>
						<ul id="sub-menu-2" class="sub-menu">
							<li id="menu-item-6" class="menu-item menu-item-6"><a href="https://example.com/f">Link F</a><span class="icon"></span>
								<ul id="sub-menu-3" class="sub-menu">
									<li id="menu-item-7" class="menu-item menu-item-7"><a href="https://example.com/g">Link G</a></li>
									<li id="menu-item-8" class="menu-item menu-item-8"><a href="https://example.com/h">Link H</a></li>
								</ul>
							</li>
							<li id="menu-item-9" class="menu-item menu-item-9"><a href="https://example.com/i">Link I</a></li>
						</ul>
					</li>
				</ul>
			</nav>
		';
		$dom  = AMP_DOM_Utils::get_dom_from_content( $html );
		return [
			// First sub-menu.
			[ $dom, $dom->xpath->query( "//*[ @id = 'menu-item-2' ]" )->item( 0 ), $dom->xpath->query( "//*[ @id = 'sub-menu-1' ]" )->item( 0 ) ],

			// Second sub-menu.
			[ $dom, $dom->xpath->query( "//*[ @id = 'menu-item-5' ]" )->item( 0 ), $dom->xpath->query( "//*[ @id = 'sub-menu-2' ]" )->item( 0 ) ],

			// Sub-menu of second sub-menu.
			[ $dom, $dom->xpath->query( "//*[ @id = 'menu-item-6' ]" )->item( 0 ), $dom->xpath->query( "//*[ @id = 'sub-menu-3' ]" )->item( 0 ) ],
		];
	}

	/**
	 * Test get_closest_submenu().
	 *
	 * @dataProvider get_get_closest_submenu_data
	 * @covers AMP_Core_Theme_Sanitizer::get_closest_submenu
	 *
	 * @param Document   $dom      Document.
	 * @param DOMElement $element  Element.
	 * @param DOMElement $expected Expected element.
	 */
	public function test_get_closest_submenu( $dom, $element, $expected ) {
		$sanitizer = new AMP_Core_Theme_Sanitizer( $dom );
		$actual    = $this->call_private_method( $sanitizer, 'get_closest_submenu', [ $element ] );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test get_supported_themes().
	 *
	 * @covers AMP_Core_Theme_Sanitizer::get_supported_themes()
	 */
	public function test_get_supported_themes() {
		$supported_themes = [
			'twentytwenty',
			'twentynineteen',
			'twentyseventeen',
			'twentysixteen',
			'twentyfifteen',
			'twentyfourteen',
			'twentythirteen',
			'twentytwelve',
			'twentyeleven',
			'twentyten',
		];

		$this->assertEquals( $supported_themes, AMP_Core_Theme_Sanitizer::get_supported_themes() );
	}

	/**
	 * Test extend_theme_support().
	 *
	 * @covers AMP_Core_Theme_Sanitizer::extend_theme_support()
	 */
	public function test_extend_theme_support() {
		$theme_dir = basename( dirname( AMP__DIR__ ) ) . '/' . basename( AMP__DIR__ ) . '/tests/php/data/themes';
		register_theme_directory( $theme_dir );

		// Make sure that theme support is added even when no special keys are needed.
		remove_theme_support( 'amp' );
		switch_theme( 'twentytwenty' );
		AMP_Core_Theme_Sanitizer::extend_theme_support();
		$this->assertTrue( current_theme_supports( 'amp' ) );
		$this->assertEquals(
			[ 'paired' => true ],
			AMP_Theme_Support::get_theme_support_args()
		);

		// Make sure the expected theme support is added for a core theme.
		remove_theme_support( 'amp' );
		switch_theme( 'twentysixteen' );
		AMP_Core_Theme_Sanitizer::extend_theme_support();
		$this->assertTrue( current_theme_supports( 'amp' ) );
		$this->assertEqualSets(
			[ 'paired', 'nav_menu_toggle', 'nav_menu_dropdown' ],
			array_keys( AMP_Theme_Support::get_theme_support_args() )
		);

		// Ensure custom themes do not get extended with theme support.
		remove_theme_support( 'amp' );
		$this->assertTrue( wp_get_theme( 'custom' )->exists() );
		switch_theme( 'custom' );
		AMP_Core_Theme_Sanitizer::extend_theme_support();
		$this->assertFalse( current_theme_supports( 'amp' ) );
		$this->assertFalse( AMP_Theme_Support::get_theme_support_args() );

		// Ensure that child theme inherits extended core theme support.
		$this->assertTrue( wp_get_theme( 'child-of-core' )->exists() );
		switch_theme( 'child-of-core' );
		AMP_Core_Theme_Sanitizer::extend_theme_support();
		$this->assertTrue( current_theme_supports( 'amp' ) );
		$this->assertEqualSets(
			[ 'paired', 'nav_menu_toggle', 'nav_menu_dropdown' ],
			array_keys( AMP_Theme_Support::get_theme_support_args() )
		);
	}

	/**
	 * Data for testing acceptable errors for supported themes.
	 *
	 * @return array
	 */
	public function get_templates() {
		$not_supported = [ 'foo', 'bar' ];

		$templates = array_merge( $not_supported, AMP_Core_Theme_Sanitizer::get_supported_themes() );

		return array_map(
			static function ( $template ) use ( $not_supported ) {
				if ( in_array( $template, $not_supported, true ) ) {
					$acceptable_errors = [];
				} else {
					$acceptable_errors = [
						AMP_Style_Sanitizer::CSS_SYNTAX_INVALID_AT_RULE => [
							[
								'at_rule' => 'viewport',
							],
							[
								'at_rule' => '-ms-viewport',
							],
						],
					];
				}

				return [ $template, $acceptable_errors ];
			},
			$templates
		);
	}

	/**
	 * Test add_has_header_video_body_class().
	 *
	 * @covers AMP_Core_Theme_Sanitizer::add_has_header_video_body_class
	 */
	public function test_add_has_header_video_body_class() {
		$args = [ 'foo' ];

		// Without has_header_video().
		AMP_Core_Theme_Sanitizer::add_has_header_video_body_class( $args );

		$expected = [ 'foo' ];
		$actual   = apply_filters( 'body_class', $args );
		$this->assertEquals( $expected, $actual );

		// With has_header_video().
		remove_all_filters( 'body_class' );

		add_filter(
			'get_header_video_url',
			static function () {
				return 'https://example.com';
			}
		);

		AMP_Core_Theme_Sanitizer::add_has_header_video_body_class( $args );
		$expected = [ 'foo', 'has-header-video' ];
		$actual   = apply_filters( 'body_class', $args );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Data for testing guessing of modal roles.
	 *
	 * @return array
	 */
	public function get_modals() {
		$dom         = new Document();
		$modal_roles = $this->get_static_private_property( 'AMP_Core_Theme_Sanitizer', 'modal_roles' );

		$a = array_map(
			static function ( $rule ) use ( $dom ) {
				return [ AMP_DOM_Utils::create_node( $dom, 'div', [ 'class' => $rule ] ), $rule ];
			},
			$modal_roles
		);

		return array_merge(
			$a,
			[
				[ AMP_DOM_Utils::create_node( $dom, 'div', [ 'foo' => 'bar' ] ), 'dialog' ],
				[ AMP_DOM_Utils::create_node( $dom, 'div', [ 'class' => 'foo' ] ), 'dialog' ],
				[ AMP_DOM_Utils::create_node( $dom, 'div', [ 'class' => 'top_navigation' ] ), 'dialog' ],
				[ AMP_DOM_Utils::create_node( $dom, 'div', [ 'class' => ' a	search  c ' ] ), 'search' ],
			]
		);
	}

	/**
	 * Test guess_modal_role().
	 *
	 * @dataProvider get_modals
	 * @covers       AMP_Core_Theme_Sanitizer::guess_modal_role
	 *
	 * @param DOMElement $dom_element Document.
	 * @param string     $expected    Expected.
	 * @throws ReflectionException
	 */
	public function test_guess_modal_role( DOMElement $dom_element, $expected ) {
		$sanitizer = new AMP_Core_Theme_Sanitizer( new Document() );
		$actual    = $this->call_private_method( $sanitizer, 'guess_modal_role', [ $dom_element ] );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests add_img_display_block_fix.
	 *
	 * @covers AMP_Core_Theme_Sanitizer::add_img_display_block_fix
	 */
	public function test_add_img_display_block_fix() {
		AMP_Core_Theme_Sanitizer::add_img_display_block_fix();
		ob_start();
		wp_print_styles();
		$output = ob_get_clean();
		$this->assertRegExp( '/amp-img.+display.+block/s', $output );
	}

	/**
	 * Tests add_twentytwenty_custom_logo_fix.
	 *
	 * @covers AMP_Core_Theme_Sanitizer::add_twentytwenty_custom_logo_fix
	 */
	public function test_add_twentytwenty_custom_logo_fix() {
		add_filter(
			'get_custom_logo',
			static function () {
				return '<img src="https://example.com/logo.jpg" width="100" height="200">';
			}
		);

		AMP_Core_Theme_Sanitizer::add_twentytwenty_custom_logo_fix();
		$logo = get_custom_logo();

		$needle = '.site-logo amp-img { width: 3.000000rem; } @media (min-width: 700px) { .site-logo amp-img { width: 4.500000rem; } }';

		$this->assertStringContains( $needle, $logo );
	}

	/**
	 * Tests prevent_sanitize_in_customizer_preview.
	 *
	 * @covers AMP_Core_Theme_Sanitizer::prevent_sanitize_in_customizer_preview
	 */
	public function test_prevent_sanitize_in_customizer_preview() {
		global $wp_customize;

		require_once ABSPATH . 'wp-includes/class-wp-customize-manager.php';
		$wp_customize = new \WP_Customize_Manager();

		$xpath_selectors = [ '//p[ @id = "foo" ]' ];

		$html     = '<p id="foo"></p> <p id="bar"></p>';
		$expected = '<p id="foo" data-ampdevmode=""></p> <p id="bar"></p>';

		$dom       = AMP_DOM_Utils::get_dom_from_content( $html );
		$sanitizer = new AMP_Core_Theme_Sanitizer( $dom );

		$wp_customize->start_previewing_theme();
		$sanitizer->prevent_sanitize_in_customizer_preview( $xpath_selectors );
		$wp_customize->stop_previewing_theme();

		$content = AMP_DOM_Utils::get_content_from_dom( $dom );

		$this->assertEquals( $expected, $content );
	}
}
