<?php
/**
 * Tests for ReaderThemes.
 *
 * @package AMP
 * @since 2.0
 */

namespace AmpProject\AmpWP\Tests\Admin;

use AMP_Options_Manager;
use AMP_Theme_Support;
use AmpProject\AmpWP\Admin\ReaderThemes;
use AmpProject\AmpWP\Option;
use AmpProject\AmpWP\Tests\Helpers\LoadsCoreThemes;
use AmpProject\AmpWP\Tests\Helpers\ThemesApiRequestMocking;
use WP_UnitTestCase;
use Closure;

/**
 * Tests for reader themes.
 *
 * @group reader-themes
 *
 * @covers ReaderThemes
 */
class ReaderThemesTest extends WP_UnitTestCase {

	use ThemesApiRequestMocking, LoadsCoreThemes;

	/**
	 * Test instance.
	 *
	 * @var ReaderThemes
	 */
	private $reader_themes;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();

		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			$this->markTestSkipped( 'Requires WordPress 5.0.' );
		}

		$this->add_reader_themes_request_filter();

		switch_theme( 'twentytwenty' );
		$this->reader_themes = new ReaderThemes();

		$this->register_core_themes();
	}

	public function tearDown() {
		parent::tearDown();

		$this->restore_theme_directories();
	}

	/**
	 * Test for get_themes.
	 *
	 * @covers ReaderThemes::get_themes
	 * @covers ReaderThemes::get_default_reader_themes
	 * @covers ReaderThemes::get_classic_mode
	 */
	public function test_get_themes() {
		$themes = $this->reader_themes->get_themes();

		$this->assertEquals( 'legacy', end( $themes )['slug'] );

		$keys = [
			'name',
			'slug',
			'preview_url',
			'screenshot_url',
			'homepage',
			'description',
			'requires',
			'requires_php',
			'availability',
		];
		foreach ( $themes as $theme ) {
			$this->assertEqualSets( $keys, array_keys( $theme ) );
		}

		// Verify that the Reader theme data can be retrieved from the list of installed themes.
		register_theme_directory( __DIR__ . '/../../data/themes' );
		delete_site_transient( 'theme_roots' );

		AMP_Options_Manager::update_option( Option::READER_THEME, 'child-of-core' );

		$themes = ( new ReaderThemes() )->get_themes();

		$this->assertContains( 'child-of-core', wp_list_pluck( $themes, 'slug' ) );
	}

	/**
	 * Test for get_reader_theme_by_slug.
	 *
	 * @covers ReaderThemes::get_reader_theme_by_slug
	 */
	public function test_get_reader_theme_by_slug() {
		$this->assertFalse( $this->reader_themes->get_reader_theme_by_slug( 'some-theme' ) );
		$this->assertArrayHasKey( 'slug', $this->reader_themes->get_reader_theme_by_slug( 'legacy' ) );
	}

	/**
	 * Provides test themes to test availability.
	 *
	 * @return array
	 */
	public function get_availability_test_themes() {
		return [
			'from_wp_future'                         => [
				static function () {
					return ReaderThemes::STATUS_NON_INSTALLABLE;
				},
				false,
				[
					'name'         => 'Some Theme',
					'requires'     => '99.9',
					'requires_php' => '5.2',
					'slug'         => 'from_wp_future',
				],
			],
			'from_php_future'                        => [
				static function () {
					return ReaderThemes::STATUS_NON_INSTALLABLE;
				},
				false,
				[
					'name'         => 'Some Theme',
					'requires'     => '4.9',
					'requires_php' => '99.9',
					'slug'         => 'from_php_future',
				],
			],
			'non_reader_theme'                       => [
				static function () {
					return wp_get_theme( 'neve' )->exists() ? ReaderThemes::STATUS_INSTALLED : ReaderThemes::STATUS_INSTALLABLE;
				},
				true,
				[
					'name'         => 'Neve',
					'requires'     => false,
					'requires_php' => '5.2',
					'slug'         => 'neve',
				],
			],
			'twentytwelve_not_requiring_wp_version'  => [
				static function () {
					return wp_get_theme( 'twentytwelve' )->exists() ? ReaderThemes::STATUS_INSTALLED : ReaderThemes::STATUS_NON_INSTALLABLE;
				},
				true,
				[
					'name'         => 'Some Theme',
					'requires'     => false,
					'requires_php' => '5.2',
					'slug'         => 'twentytwelve',
				],
			],
			'twentytwelve_not_requiring_php_version' => [
				static function () {
					return wp_get_theme( 'twentysixteen' )->exists() ? ReaderThemes::STATUS_INSTALLED : ReaderThemes::STATUS_NON_INSTALLABLE;
				},
				true,
				[
					'name'         => 'Some Theme',
					'requires'     => '4.9',
					'requires_php' => false,
					'slug'         => 'twentysixteen',
				],
			],
			'twentytwenty_active'                    => [
				static function () {
					return ReaderThemes::STATUS_ACTIVE;
				},
				true,
				[
					'name'         => 'WordPress Default',
					'requires'     => '4.4',
					'requires_php' => '5.2',
					'slug'         => 'twentytwenty',
				],
			],
		];
	}

	/**
	 * Test for get_theme_availability.
	 *
	 * @covers ReaderThemes::get_theme_availability
	 * @covers ReaderThemes::can_install_theme
	 *
	 * @dataProvider get_availability_test_themes
	 *
	 * @param Closure $get_expected Expected.
	 * @param bool    $can_install  Can install.
	 * @param array   $theme        Theme.
	 */
	public function test_get_theme_availability( $get_expected, $can_install, $theme ) {
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );
		$expected = $get_expected();
		$this->assertEquals( $expected, $this->reader_themes->get_theme_availability( $theme ) );
		$this->assertEquals( $can_install, $this->reader_themes->can_install_theme( $theme ) );
	}

	/**
	 * Tests for can_install_theme.
	 *
	 * @covers ReaderThemes::can_install_theme
	 */
	public function test_can_install_theme() {
		$core_theme = [
			'name'         => 'Twenty Twelve',
			'requires'     => false,
			'requires_php' => '5.2',
			'slug'         => 'twentytwelve',
		];

		$neve_theme = [
			'name'         => 'Neve',
			'requires'     => false,
			'requires_php' => '5.2',
			'slug'         => 'neve',
		];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'author' ] ) );
		$this->assertFalse( $this->reader_themes->can_install_theme( $core_theme ) );
		$this->assertFalse( $this->reader_themes->can_install_theme( $neve_theme ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->assertTrue( $this->reader_themes->can_install_theme( $core_theme ) );
		$this->assertTrue( $this->reader_themes->can_install_theme( $neve_theme ) );

		$core_theme['requires'] = '999.9';
		$this->assertFalse( $this->reader_themes->can_install_theme( $core_theme ) );

		$core_theme['requires']     = false;
		$core_theme['requires_php'] = '999.9';
		$this->assertFalse( $this->reader_themes->can_install_theme( $core_theme ) );
	}

	/**
	 * Tests for theme_data_exists.
	 *
	 * @covers ReaderThemes::theme_data_exists
	 */
	public function test_theme_data_exists() {
		$this->assertFalse( ( new ReaderThemes() )->theme_data_exists( 'neve' ) );

		$neve_theme        = [
			'name'         => 'Neve',
			'requires'     => false,
			'requires_php' => '5.2',
			'slug'         => 'neve',
		];
		$append_neve_theme = static function ( $themes ) use ( $neve_theme ) {
			$themes[] = $neve_theme;
			return $themes;
		};

		add_filter( 'amp_reader_themes', $append_neve_theme );

		$this->assertTrue( ( new ReaderThemes() )->theme_data_exists( 'neve' ) );

		remove_filter( 'amp_reader_themes', $append_neve_theme );
	}

	/** @covers ReaderThemes::using_fallback_theme */
	public function test_using_fallback_theme() {
		$reader_themes = new ReaderThemes();
		AMP_Options_Manager::update_options(
			[
				Option::THEME_SUPPORT => 'reader',
				Option::READER_THEME  => ReaderThemes::DEFAULT_READER_THEME,
			]
		);
		$this->assertFalse( $reader_themes->using_fallback_theme() );

		AMP_Options_Manager::update_option( Option::READER_THEME, 'foobar' );
		$this->assertTrue( $reader_themes->using_fallback_theme() );

		AMP_Options_Manager::update_option( Option::THEME_SUPPORT, AMP_Theme_Support::TRANSITIONAL_MODE_SLUG );
		$this->assertFalse( $reader_themes->using_fallback_theme() );
	}
}
