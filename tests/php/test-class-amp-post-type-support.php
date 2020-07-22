<?php
/**
 * Tests for Post Types Support.
 *
 * @package AMP
 * @since 0.6
 */

/**
 * Tests for Post Type Support.
 *
 * @covers AMP_Post_Type_Support
 */
class Test_AMP_Post_Type_Support extends WP_UnitTestCase {

	/**
	 * After a test method runs, reset any state in WordPress the test method might have changed.
	 */
	public function tearDown() {
		parent::tearDown();
		foreach ( [ 'book', 'poem', 'secret' ] as $post_type ) {
			unregister_post_type( $post_type );
		}
	}

	/**
	 * Test get_eligible_post_types.
	 *
	 * @covers AMP_Post_Type_Support::get_eligible_post_types()
	 */
	public function test_get_eligible_post_types() {
		register_post_type(
			'book',
			[
				'label'  => 'Book',
				'public' => true,
			]
		);
		register_post_type(
			'secret',
			[
				'label'  => 'Secret',
				'public' => false,
			]
		);

		$this->assertEqualSets(
			[
				'post',
				'page',
				'attachment',
				'book',
			],
			AMP_Post_Type_Support::get_eligible_post_types()
		);
	}

	/**
	 * Test add_post_type_support.
	 *
	 * @covers AMP_Post_Type_Support::add_post_type_support()
	 */
	public function test_add_post_type_support() {
		remove_theme_support( AMP_Theme_Support::SLUG );
		register_post_type(
			'book',
			[
				'label'  => 'Book',
				'public' => true,
			]
		);
		register_post_type(
			'poem',
			[
				'label'  => 'Poem',
				'public' => true,
			]
		);
		AMP_Options_Manager::update_option( 'supported_post_types', [ 'post', 'poem' ] );

		AMP_Post_Type_Support::add_post_type_support();
		$this->assertTrue( post_type_supports( 'post', AMP_Post_Type_Support::SLUG ) );
		$this->assertTrue( post_type_supports( 'poem', AMP_Post_Type_Support::SLUG ) );
		$this->assertFalse( post_type_supports( 'book', AMP_Post_Type_Support::SLUG ) );
	}

	/**
	 * Return an error code if a given post does not have AMP support.
	 *
	 * @covers AMP_Post_Type_Support::get_support_errors()
	 */
	public function test_get_support_error() {
		remove_theme_support( AMP_Theme_Support::SLUG );
		register_post_type(
			'book',
			[
				'label'  => 'Book',
				'public' => true,
			]
		);

		// Post type support.
		$book_id = self::factory()->post->create( [ 'post_type' => 'book' ] );
		$this->assertEquals( [ 'post-type-support' ], AMP_Post_Type_Support::get_support_errors( $book_id ) );
		add_post_type_support( 'book', AMP_Post_Type_Support::SLUG );
		$this->assertEmpty( AMP_Post_Type_Support::get_support_errors( $book_id ) );

		// Skip-post.
		add_filter( 'amp_skip_post', '__return_true' );
		$this->assertEquals( [ 'skip-post' ], AMP_Post_Type_Support::get_support_errors( $book_id ) );
		remove_filter( 'amp_skip_post', '__return_true' );
		$this->assertEmpty( AMP_Post_Type_Support::get_support_errors( $book_id ) );
	}
}
