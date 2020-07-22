<?php
/**
 * Tests for class AMP_Widget_Archives.
 *
 * @package AMP
 */

/**
 * Tests for class AMP_Widget_Archives.
 *
 * @package AMP
 */
class Test_AMP_Widget_Archives extends WP_UnitTestCase {

	/**
	 * Instance of the widget.
	 *
	 * @var object.
	 */
	public $widget;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		delete_option( AMP_Options_Manager::OPTION_NAME ); // Make sure default reader mode option does not override theme support being added.
		add_theme_support( AMP_Theme_Support::SLUG );
		wp_maybe_load_widgets();
		AMP_Theme_Support::init();
		$this->widget = new AMP_Widget_Archives();
	}

	/**
	 * Tear down.
	 *
	 * @inheritdoc
	 */
	public function tearDown() {
		parent::tearDown();
		remove_theme_support( AMP_Theme_Support::SLUG );
	}

	/**
	 * Test construct().
	 *
	 * @see AMP_Widget_Archives::__construct().
	 */
	public function test_construct() {
		$this->assertInstanceOf( '\\AMP_Widget_Archives', $this->widget );
		$this->assertEquals( 'archives', $this->widget->id_base );
		$this->assertEquals( 'Archives', $this->widget->name );
		$this->assertEquals( 'widget_archive', $this->widget->widget_options['classname'] );
		$this->assertEquals( true, $this->widget->widget_options['customize_selective_refresh'] );
		$this->assertEquals( 'A monthly archive of your site&#8217;s Posts.', $this->widget->widget_options['description'] );
	}

	/**
	 * Test widget().
	 *
	 * @see AMP_Widget_Archives::widget().
	 */
	public function test_widget() {
		wp();
		$this->assertTrue( is_amp_endpoint() );
		$arguments = [
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		];
		$instance  = [
			'title'    => 'Test Archives Widget',
			'dropdown' => 1,
		];
		$output    = get_echo( [ $this->widget, 'widget' ], [ $arguments, $instance ] );

		$this->assertContains( 'on="change:AMP.navigateTo(url=event.value)"', $output );
		$this->assertNotContains( 'onchange=', $output );
	}

}
