<?php
/**
 * Tests for AMP_Validated_URL_Post_Type class.
 *
 * @package AMP
 */

// phpcs:disable WordPress.Variables.GlobalVariables.OverrideProhibited

/**
 * Tests for AMP_Validated_URL_Post_Type class.
 *
 * @covers AMP_Validated_URL_Post_Type
 */
class Test_AMP_Validated_URL_Post_Type extends WP_UnitTestCase {

	use AMP_Test_HandleValidation;

	const TESTED_CLASS = 'AMP_Validated_URL_Post_Type';

	/**
	 * After a test method runs, reset any state in WordPress the test method might have changed.
	 */
	public function tearDown() {
		global $current_screen;
		parent::tearDown();
		$current_screen = null;
	}

	/**
	 * Test register.
	 *
	 * @covers \AMP_Validated_URL_Post_Type::register()
	 * @covers \AMP_Validated_URL_Post_Type::add_admin_hooks()
	 */
	public function test_register() {
		add_theme_support( AMP_Theme_Support::SLUG );
		$this->assertFalse( is_admin() );
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		AMP_Validated_URL_Post_Type::register();
		$amp_post_type = get_post_type_object( AMP_Validated_URL_Post_Type::POST_TYPE_SLUG );

		$this->assertContains( AMP_Validated_URL_Post_Type::POST_TYPE_SLUG, get_post_types() );
		$this->assertEquals( [], get_all_post_type_supports( AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ) );
		$this->assertEquals( AMP_Validated_URL_Post_Type::POST_TYPE_SLUG, $amp_post_type->name );
		$this->assertEquals( 'AMP Validated URLs', $amp_post_type->label );
		$this->assertEquals( false, $amp_post_type->public );
		$this->assertTrue( $amp_post_type->show_ui );
		$this->assertEquals( AMP_Options_Manager::OPTION_NAME, $amp_post_type->show_in_menu );
		$this->assertTrue( $amp_post_type->show_in_admin_bar );
		$this->assertNotContains( AMP_Validated_URL_Post_Type::REMAINING_ERRORS, wp_removable_query_args() );

		// Make sure that add_admin_hooks() gets called.
		set_current_screen( 'index.php' );
		AMP_Validated_URL_Post_Type::register();
		$this->assertContains( AMP_Validated_URL_Post_Type::REMAINING_ERRORS, wp_removable_query_args() );
	}

	/**
	 * Test add_admin_hooks.
	 *
	 * @covers \AMP_Validated_URL_Post_Type::add_admin_hooks()
	 */
	public function test_add_admin_hooks() {
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );
		AMP_Validated_URL_Post_Type::add_admin_hooks();

		$this->assertEquals( 10, has_filter( 'dashboard_glance_items', [ self::TESTED_CLASS, 'filter_dashboard_glance_items' ] ) );
		$this->assertEquals( 10, has_action( 'rightnow_end', [ self::TESTED_CLASS, 'print_dashboard_glance_styles' ] ) );

		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', [ self::TESTED_CLASS, 'enqueue_edit_post_screen_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'add_meta_boxes', [ self::TESTED_CLASS, 'add_meta_boxes' ] ) );
		$this->assertEquals( 10, has_action( 'edit_form_top', [ self::TESTED_CLASS, 'print_url_as_title' ] ) );

		$this->assertEquals( 10, has_filter( 'the_title', [ self::TESTED_CLASS, 'filter_the_title_in_post_list_table' ] ) );
		$this->assertEquals( 10, has_filter( 'restrict_manage_posts', [ self::TESTED_CLASS, 'render_post_filters' ] ) );
		$this->assertEquals( 10, has_filter( 'manage_' . AMP_Validated_URL_Post_Type::POST_TYPE_SLUG . '_posts_columns', [ self::TESTED_CLASS, 'add_post_columns' ] ) );
		$this->assertEquals( 10, has_filter( 'manage_' . AMP_Validated_URL_Post_Type::POST_TYPE_SLUG . '_columns', [ self::TESTED_CLASS, 'add_single_post_columns' ] ) );
		$this->assertEquals( 10, has_action( 'manage_posts_custom_column', [ self::TESTED_CLASS, 'output_custom_column' ] ) );
		$this->assertEquals( 10, has_filter( 'post_row_actions', [ self::TESTED_CLASS, 'filter_post_row_actions' ] ) );
		$this->assertEquals( 10, has_filter( 'bulk_actions-edit-' . AMP_Validated_URL_Post_Type::POST_TYPE_SLUG, [ self::TESTED_CLASS, 'filter_bulk_actions' ] ) );
		$this->assertEquals( 10, has_filter( 'bulk_actions-' . AMP_Validated_URL_Post_Type::POST_TYPE_SLUG, '__return_false' ) );
		$this->assertEquals( 10, has_filter( 'handle_bulk_actions-edit-' . AMP_Validated_URL_Post_Type::POST_TYPE_SLUG, [ self::TESTED_CLASS, 'handle_bulk_action' ] ) );
		$this->assertEquals( 10, has_action( 'admin_notices', [ self::TESTED_CLASS, 'print_admin_notice' ] ) );
		$this->assertEquals( 10, has_action( 'admin_action_' . AMP_Validated_URL_Post_Type::VALIDATE_ACTION, [ self::TESTED_CLASS, 'handle_validate_request' ] ) );
		$this->assertEquals( 10, has_action( 'post_action_' . AMP_Validated_URL_Post_Type::UPDATE_POST_TERM_STATUS_ACTION, [ self::TESTED_CLASS, 'handle_validation_error_status_update' ] ) );
		$this->assertEquals( 10, has_action( 'admin_menu', [ self::TESTED_CLASS, 'add_admin_menu_new_invalid_url_count' ] ) );

		$post = self::factory()->post->create_and_get( [ 'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ] );
		$this->assertEquals( '', apply_filters( 'post_date_column_status', 'publish', $post ) );
		$this->assertEquals( 'publish', apply_filters( 'post_date_column_status', 'publish', self::factory()->post->create_and_get() ) );

		$this->assertContains( 'amp_actioned', wp_removable_query_args() );
		$this->assertContains( 'amp_taxonomy_terms_updated', wp_removable_query_args() );
		$this->assertContains( AMP_Validated_URL_Post_Type::REMAINING_ERRORS, wp_removable_query_args() );
		$this->assertContains( 'amp_urls_tested', wp_removable_query_args() );
		$this->assertContains( 'amp_validate_error', wp_removable_query_args() );
	}

	/**
	 * Test add_admin_menu_new_invalid_url_count.
	 *
	 * @covers \AMP_Validated_URL_Post_Type::add_admin_menu_new_invalid_url_count()
	 */
	public function test_add_admin_menu_new_invalid_url_count() {
		global $submenu;
		AMP_Validation_Manager::init(); // Register the post type and taxonomy.

		unset( $submenu[ AMP_Options_Manager::OPTION_NAME ] );
		AMP_Validated_URL_Post_Type::add_admin_menu_new_invalid_url_count();

		$submenu[ AMP_Options_Manager::OPTION_NAME ] = [
			0 => [
				0 => 'General',
				1 => 'manage_options',
				2 => 'amp-options',
				3 => 'AMP Settings',
			],
			1 => [
				0 => 'Analytics',
				1 => 'manage_options',
				2 => 'amp-analytics-options',
				3 => 'AMP Analytics Options',
			],
			2 => [
				0 => 'Invalid Pages',
				1 => 'edit_posts',
				2 => 'edit.php?post_type=amp_validated_url',
				3 => 'Invalid AMP Pages (URLs)',
			],
		];

		$invalid_url_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[
					'code' => 'hello',
				],
			],
			get_permalink( self::factory()->post->create() )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_url_post_id );

		AMP_Validated_URL_Post_Type::add_admin_menu_new_invalid_url_count();

		$this->assertContains( '<span class="awaiting-mod"><span class="new-validation-error-urls-count">1</span></span>', $submenu[ AMP_Options_Manager::OPTION_NAME ][2][0] );
	}

	/**
	 * Test get_invalid_url_validation_errors and display_invalid_url_validation_error_counts_summary.
	 *
	 * @covers \AMP_Validated_URL_Post_Type::get_invalid_url_validation_errors()
	 * @covers \AMP_Validated_URL_Post_Type::display_invalid_url_validation_error_counts_summary()
	 * @covers \AMP_Validated_URL_Post_Type::store_validation_errors()
	 */
	public function test_get_invalid_url_validation_errors() {
		$this->accept_sanitization_by_default( false );
		add_theme_support( AMP_Theme_Support::SLUG, [ AMP_Theme_Support::PAIRED_FLAG => true ] );
		AMP_Validation_Manager::init();
		$post = self::factory()->post->create();
		$this->assertEmpty( AMP_Validated_URL_Post_Type::get_invalid_url_validation_errors( get_permalink( $post ) ) );

		add_filter(
			'amp_validation_error_default_sanitized',
			static function( $sanitized, $error ) {
				if ( 'new accepted' === $error['code'] ) {
					$sanitized = true;
				} elseif ( 'new rejected' === $error['code'] ) {
					$sanitized = false;
				}
				return $sanitized;
			},
			10,
			2
		);

		add_filter(
			'amp_validation_error_sanitized',
			static function( $sanitized, $error ) {
				if ( 'accepted' === $error['code'] ) {
					$sanitized = true;
				} elseif ( 'rejected' === $error['code'] ) {
					$sanitized = false;
				}
				return $sanitized;
			},
			10,
			2
		);

		$invalid_url_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'accepted' ],
				[ 'code' => 'rejected' ],
				[ 'code' => 'new accepted' ],
				[ 'code' => 'new rejected' ],
			],
			get_permalink( $post )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_url_post_id );

		$errors = AMP_Validated_URL_Post_Type::get_invalid_url_validation_errors( get_permalink( $post ) );
		$this->assertCount( 4, $errors );

		$error = array_shift( $errors );
		$this->assertEquals( 'accepted', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_ACCEPTED_STATUS, $error['term_status'] );
		$error = array_shift( $errors );
		$this->assertEquals( 'rejected', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_REJECTED_STATUS, $error['term_status'] );
		$error = array_shift( $errors );
		$this->assertEquals( 'new accepted', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_ACCEPTED_STATUS, $error['term_status'] );
		$error = array_shift( $errors );
		$this->assertEquals( 'new rejected', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_REJECTED_STATUS, $error['term_status'] );

		$errors = AMP_Validated_URL_Post_Type::get_invalid_url_validation_errors( get_permalink( $post ), [ 'ignore_accepted' => true ] );
		$this->assertCount( 2, $errors );
		$error = array_shift( $errors );
		$this->assertEquals( 'rejected', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_REJECTED_STATUS, $error['term_status'] );
		$error = array_shift( $errors );
		$this->assertEquals( 'new rejected', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_REJECTED_STATUS, $error['term_status'] );

		$summary = get_echo( [ 'AMP_Validated_URL_Post_Type', 'display_invalid_url_validation_error_counts_summary' ], [ $invalid_url_post_id ] );
		$this->assertContains( 'Invalid markup kept: 2', $summary );
		$this->assertContains( 'Invalid markup removed: 2', $summary );
	}

	/**
	 * Test for get_invalid_url_post().
	 *
	 * @covers \AMP_Validated_URL_Post_Type::get_invalid_url_post()
	 */
	public function test_get_invalid_url_post() {
		add_theme_support( AMP_Theme_Support::SLUG, [ AMP_Theme_Support::PAIRED_FLAG => true ] );
		AMP_Validation_Manager::init();
		$post = self::factory()->post->create_and_get();
		$this->assertEquals( null, AMP_Validated_URL_Post_Type::get_invalid_url_post( get_permalink( $post ) ) );

		$invalid_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'foo' ],
			],
			get_permalink( $post )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_post_id );

		$this->assertEquals(
			$invalid_post_id,
			AMP_Validated_URL_Post_Type::get_invalid_url_post( get_permalink( $post ) )->ID
		);

		// Test trashed.
		wp_trash_post( $invalid_post_id );
		$this->assertNull( AMP_Validated_URL_Post_Type::get_invalid_url_post( get_permalink( $post ) ) );
		$args = [ 'include_trashed' => true ];
		$this->assertEquals(
			$invalid_post_id,
			AMP_Validated_URL_Post_Type::get_invalid_url_post( get_permalink( $post ), $args )->ID
		);
		wp_untrash_post( $invalid_post_id );

		// Test normalized.
		$args = [ 'normalize' => false ];
		$url  = add_query_arg(
			array_fill_keys( wp_removable_query_args(), 'true' ),
			get_permalink( $post ) . '#baz'
		);
		$url  = set_url_scheme( $url, 'http' );
		$this->assertNull( AMP_Validated_URL_Post_Type::get_invalid_url_post( $url, $args ) );
		$args = [ 'normalize' => true ];
		$this->assertEquals( $invalid_post_id, AMP_Validated_URL_Post_Type::get_invalid_url_post( $url, $args )->ID );
		$this->assertEquals( $invalid_post_id, AMP_Validated_URL_Post_Type::get_invalid_url_post( $url )->ID );
		$url = set_url_scheme( get_permalink( $post ), 'http' );
		$this->assertNull( AMP_Validated_URL_Post_Type::get_invalid_url_post( $url, [ 'normalize' => false ] ) );
		$this->assertEquals( $invalid_post_id, AMP_Validated_URL_Post_Type::get_invalid_url_post( $url, [ 'normalize' => true ] )->ID );
	}

	/**
	 * Test get_url_from_post.
	 *
	 * @covers \AMP_Validated_URL_Post_Type::get_url_from_post()
	 */
	public function test_get_url_from_post() {
		add_theme_support( AMP_Theme_Support::SLUG, [ AMP_Theme_Support::PAIRED_FLAG => true ] );
		AMP_Validation_Manager::init();
		$post = self::factory()->post->create_and_get();

		$this->assertNull( AMP_Validated_URL_Post_Type::get_url_from_post( 0 ) );
		$this->assertNull( AMP_Validated_URL_Post_Type::get_url_from_post( $post ) );

		$invalid_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'foo' ],
			],
			get_permalink( $post )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_post_id );

		$this->assertEquals(
			add_query_arg( amp_get_slug(), '', get_permalink( $post ) ),
			AMP_Validated_URL_Post_Type::get_url_from_post( $invalid_post_id )
		);

		add_theme_support( AMP_Theme_Support::SLUG, [ AMP_Theme_Support::PAIRED_FLAG => false ] );
		$this->assertEquals(
			get_permalink( $post ),
			AMP_Validated_URL_Post_Type::get_url_from_post( $invalid_post_id )
		);

		// Check URL scheme.
		add_filter(
			'home_url',
			static function ( $url ) {
				return set_url_scheme( $url, 'http' );
			},
			10
		);
		$this->assertEquals( 'http', wp_parse_url( AMP_Validated_URL_Post_Type::get_url_from_post( $invalid_post_id ), PHP_URL_SCHEME ) );
		add_filter(
			'home_url',
			static function ( $url ) {
				return set_url_scheme( $url, 'https' );
			},
			10
		);
		$this->assertEquals( 'https', wp_parse_url( AMP_Validated_URL_Post_Type::get_url_from_post( $invalid_post_id ), PHP_URL_SCHEME ) );
	}

	/**
	 * Test for store_validation_errors()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::store_validation_errors()
	 */
	public function test_store_validation_errors() {
		global $post;
		add_theme_support( AMP_Theme_Support::SLUG, [ AMP_Theme_Support::PAIRED_FLAG => true ] );
		AMP_Validation_Manager::init();
		$post = self::factory()->post->create_and_get();

		add_filter(
			'amp_validation_error_sanitized',
			static function( $sanitized, $error ) {
				if ( 'accepted' === $error['code'] ) {
					$sanitized = true;
				} elseif ( 'rejected' === $error['code'] ) {
					$sanitized = false;
				}
				return $sanitized;
			},
			10,
			2
		);

		$errors = [
			[
				'code'    => 'accepted',
				'sources' => [
					[
						'type' => 'plugin',
						'name' => 'amp',
						'evil' => '<script>\o/</script>', // Test slash preservation and kses suspension.
					],
				],
			],
			[
				'code'    => 'rejected',
				'evil'    => '<script>\o/</script>', // Test slash preservation and kses suspension.
				'sources' => [
					[
						'type' => 'theme',
						'name' => 'twentyseventeen',
					],
				],
			],
			[
				'code'    => 'rejected',
				'evil'    => '<script>document.write( \'<a href="#" target="_blank" rel="noopener noreferrer">test</a>\' );</script>', // Test protection against wp_targeted_link_rel JSON corruption.
				'sources' => [
					[
						'type' => 'theme',
						'name' => 'twentyseventeen',
					],
				],
			],
			[
				'code'    => 'new',
				'sources' => [
					[
						'type' => 'core',
						'name' => 'wp-includes',
					],
				],
			],
		];

		$invalid_url_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			$errors,
			get_permalink( $post ),
			[
				'invalid_url_post' => 0,
			]
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_url_post_id );
		$this->assertNotEquals( $invalid_url_post_id, $post->ID, 'Passing an empty invalid_url_post should not re-use the global $post ever.' );

		// Test resurrection from trash.
		wp_trash_post( $invalid_url_post_id );
		$this->assertEquals(
			$invalid_url_post_id,
			AMP_Validated_URL_Post_Type::store_validation_errors(
				$errors,
				get_permalink( $post ),
				[
					'queried_object' => [
						'type' => 'post',
						'id'   => $post->ID,
					],
				]
			)
		);
		$this->assertEquals( 'publish', get_post_status( $invalid_url_post_id ) );
		$this->assertEquals(
			[
				'type' => 'post',
				'id'   => $post->ID,
			],
			get_post_meta( $invalid_url_post_id, '_amp_queried_object', true )
		);

		// Test passing specific post to override the URL.
		$this->assertEquals(
			$invalid_url_post_id,
			AMP_Validated_URL_Post_Type::store_validation_errors(
				$errors,
				home_url( '/something/else/' ),
				[
					'invalid_url_post' => $invalid_url_post_id,
				]
			)
		);

		$this->assertEquals(
			home_url( '/something/else/', 'https' ),
			get_post( $invalid_url_post_id )->post_title
		);

		$stored_errors = AMP_Validated_URL_Post_Type::get_invalid_url_validation_errors( $invalid_url_post_id );
		$this->assertEquals(
			$errors,
			array_map(
				static function( $stored_error ) {
					return $stored_error['data'];
				},
				$stored_errors
			)
		);

		$error_groups = [
			AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_ACCEPTED_STATUS,
			AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_REJECTED_STATUS,
			AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_REJECTED_STATUS,
			AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_ACCEPTED_STATUS,
		];

		foreach ( $errors as $i => $error ) {
			$stored_error = $stored_errors[ $i ];

			$this->assertEquals( $error, $stored_error['data'] );

			$sourceless_error = $error;
			unset( $sourceless_error['sources'] );

			/**
			 * Term.
			 *
			 * @var WP_Term $term
			 */
			$term = $stored_error['term'];
			$this->assertEquals( $sourceless_error, json_decode( $term->description, true ) );

			$this->assertNotEmpty( get_term_meta( $term->term_id, 'created_date_gmt', true ) );
			$this->assertEquals( $error_groups[ $i ], $stored_error['term_status'] );
			$this->assertEquals( $error_groups[ $i ], $term->term_group );
		}
	}

	/**
	 * Test get_validated_environment().
	 *
	 * @covers \AMP_Validated_URL_Post_Type::get_validated_environment()
	 */
	public function test_get_validated_environment() {
		switch_theme( 'twentysixteen' );
		update_option( 'active_plugins', [ 'foo/foo.php', 'bar.php' ] );
		$old_env = AMP_Validated_URL_Post_Type::get_validated_environment();
		$this->assertArrayHasKey( 'theme', $old_env );
		$this->assertArrayHasKey( 'plugins', $old_env );
		$this->assertEquals( 'twentysixteen', $old_env['theme'] );

		switch_theme( 'twentyseventeen' );
		update_option( 'active_plugins', [ 'foo/foo.php', 'baz.php' ] );
		$new_env = AMP_Validated_URL_Post_Type::get_validated_environment();
		$this->assertNotEquals( $old_env, $new_env );
		$this->assertEquals( 'twentyseventeen', $new_env['theme'] );
	}

	/**
	 * Test get_post_staleness method.
	 *
	 * @covers AMP_Validated_URL_Post_Type::get_post_staleness()
	 * @covers AMP_Validated_URL_Post_Type::get_validated_environment()
	 */
	public function test_get_post_staleness() {
		$error = [ 'code' => 'foo' ];
		switch_theme( 'twentysixteen' );
		update_option( 'active_plugins', [ 'foo/foo.php', 'bar.php' ] );

		$invalid_url_post_id = AMP_Validated_URL_Post_Type::store_validation_errors( [ $error ], home_url( '/' ) );
		$this->assertInternalType( 'int', $invalid_url_post_id );
		$this->assertEmpty( AMP_Validated_URL_Post_Type::get_post_staleness( $invalid_url_post_id ) );

		update_option( 'active_plugins', [ 'foo/foo.php', 'baz.php' ] );
		$staleness = AMP_Validated_URL_Post_Type::get_post_staleness( $invalid_url_post_id );
		$this->assertNotEmpty( $staleness );
		$this->assertArrayHasKey( 'plugins', $staleness );
		$this->assertArrayNotHasKey( 'theme', $staleness );

		$this->assertEqualSets( [ 'baz.php' ], $staleness['plugins']['new'] );
		$this->assertEqualSets( [ 'bar.php' ], $staleness['plugins']['old'] );

		switch_theme( 'twentyseventeen' );
		$next_staleness = AMP_Validated_URL_Post_Type::get_post_staleness( $invalid_url_post_id );
		$this->assertArrayHasKey( 'theme', $next_staleness );
		$this->assertEquals( 'twentysixteen', $next_staleness['theme'] );
		$this->assertSame( $next_staleness['plugins'], $staleness['plugins'] );

		// Re-storing results updates freshness.
		AMP_Validated_URL_Post_Type::store_validation_errors( [ $error ], home_url( '/' ), $invalid_url_post_id );
		$this->assertEmpty( AMP_Validated_URL_Post_Type::get_post_staleness( $invalid_url_post_id ) );
	}

	/**
	 * Test for add_post_columns()
	 *
	 * @covers AMP_Validated_URL_Post_Type::add_post_columns()
	 */
	public function test_add_post_columns() {
		$initial_columns = [
			'cb' => '<input type="checkbox">',
		];
		$this->assertEquals(
			array_keys(
				array_merge(
					$initial_columns,
					[
						AMP_Validation_Error_Taxonomy::ERROR_STATUS => 'Status',
						AMP_Validation_Error_Taxonomy::FOUND_ELEMENTS_AND_ATTRIBUTES => 'Invalid',
						AMP_Validation_Error_Taxonomy::SOURCES_INVALID_OUTPUT => 'Sources',
					]
				)
			),
			array_keys( AMP_Validated_URL_Post_Type::add_post_columns( $initial_columns ) )
		);
	}

	/**
	 * Test for add_single_post_columns()
	 *
	 * @covers AMP_Validated_URL_Post_Type::add_single_post_columns()
	 */
	public function test_add_single_post_columns() {
		$this->assertEquals(
			[
				'cb'                          => '<input type="checkbox" />',
				'error_code'                  => 'Error',
				'status'                      => 'Markup Status<span class="dashicons dashicons-editor-help tooltip-button" tabindex="0"></span><div class="tooltip" hidden data-content="&lt;h3&gt;Markup Status&lt;/h3&gt;&lt;p&gt;When invalid markup is removed it will not block a URL from being served as AMP; the validation error will be sanitized, where the offending markup is stripped from the response to ensure AMP validity. If invalid AMP markup is kept, then URLs is occurs on will not be served as AMP pages.&lt;/p&gt;"></div>',
				'details'                     => 'Context<span class="dashicons dashicons-editor-help tooltip-button" tabindex="0"></span><div class="tooltip" hidden data-content="&lt;h3&gt;Context&lt;/h3&gt;&lt;p&gt;The parent element of where the error occurred.&lt;/p&gt;"></div>',
				'sources_with_invalid_output' => 'Sources',
				'error_type'                  => 'Type',
			],
			AMP_Validated_URL_Post_Type::add_single_post_columns()
		);
	}

	/**
	 * Gets the test data for test_output_custom_column().
	 *
	 * @return array $columns
	 */
	public function get_custom_columns() {
		$source = [
			'type' => 'plugin',
			'name' => 'AMP',
		];
		$errors = [
			[
				'code'      => AMP_Tag_And_Attribute_Sanitizer::DISALLOWED_TAG,
				'node_name' => 'script',
				'sources'   => [ $source ],
			],
			[
				'code'      => AMP_Tag_And_Attribute_Sanitizer::DISALLOWED_ATTR,
				'node_name' => 'onclick',
				'sources'   => [ $source ],
			],
		];

		return [
			AMP_Tag_And_Attribute_Sanitizer::DISALLOWED_TAG => [
				AMP_Validation_Error_Taxonomy::SOURCES_INVALID_OUTPUT,
				'<strong class="source"><span class="dashicons dashicons-admin-plugins"></span>AMP</strong>',
				$errors,
			],
			'removed_attributes'    => [
				AMP_Validation_Error_Taxonomy::FOUND_ELEMENTS_AND_ATTRIBUTES,
				'onclick',
				$errors,
			],
			'sources_invalid_input' => [
				AMP_Validation_Error_Taxonomy::SOURCES_INVALID_OUTPUT,
				'AMP',
				$errors,
			],
		];
	}

	/**
	 * Test for output_custom_column()
	 *
	 * @dataProvider get_custom_columns
	 * @covers       AMP_Validated_URL_Post_Type::output_custom_column()
	 *
	 * @param string $column_name    The name of the column.
	 * @param string $expected_value The value that is expected to be present in the column markup.
	 * @param array  $errors         Errors.
	 */
	public function test_output_custom_column( $column_name, $expected_value, $errors ) {
		AMP_Validation_Manager::init();
		$invalid_url_post_id = AMP_Validated_URL_Post_Type::store_validation_errors( $errors, home_url( '/' ) );

		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'output_custom_column' ], [ $column_name, $invalid_url_post_id ] );
		$this->assertContains( $expected_value, $output );
	}

	/**
	 * Test for render_sources_column()
	 *
	 * @covers AMP_Validated_URL_Post_Type::render_sources_column()
	 */
	public function test_render_sources_column() {
		$theme_name    = 'foo-theme';
		$post_id       = 9876;
		$error_summary = [
			'removed_attributes'          => [
				'webkitallowfullscreen' => 1,
			],
			'removed_elements'            => [],
			'sources_with_invalid_output' => [
				'embed' => true,
				'hook'  => 'the_content',
				'theme' => [ $theme_name ],
			],
		];

		// If there is an embed and a theme source, this should only output the embed icon.
		$sources_column = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$this->assertEquals( '<strong class="source"><span class="dashicons dashicons-wordpress-alt"></span>Embed</strong>', $sources_column );

		// If there is no embed source, but there is a theme, this should output the theme icon.
		unset( $error_summary['sources_with_invalid_output']['embed'] );
		$sources_column      = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$expected_theme_icon = '<strong class="source"><span class="dashicons dashicons-admin-appearance"></span>' . $theme_name . '</strong>';
		$this->assertEquals( $expected_theme_icon, $sources_column );

		// If there is a plugin and theme source, this should output icons for both of them.
		$plugin_name = 'baz-plugin';
		$error_summary['sources_with_invalid_output']['plugin'] = [ $plugin_name ];
		$expected_plugin_icon                                   = '<strong class="source"><span class="dashicons dashicons-admin-plugins"></span>' . $plugin_name . '</strong>';
		unset( $error_summary['sources_with_invalid_output']['embed'] );
		$sources_column = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$this->assertEquals( $expected_plugin_icon . $expected_theme_icon, $sources_column );

		// If there is a 'core' source, it should appear in the column output.
		$error_summary['sources_with_invalid_output']['core'] = [];
		$sources_column                                       = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$this->assertContains( '<strong><span class="dashicons dashicons-wordpress-alt"></span>Other (0)</strong>', $sources_column );

		// Even if there is a hook in the sources, it should not appear in the column if there is any other source.
		$hook_name = 'wp_header';
		$error_summary['sources_with_invalid_output']['hook'] = [ $hook_name ];
		$sources_column                                       = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$this->assertNotContains( $hook_name, $sources_column );

		// If a hook is the only source, it should appear in the column.
		$error_summary['sources_with_invalid_output'] = [ 'hook' => $hook_name ];
		$sources_column                               = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$this->assertEquals( '<strong class="source"><span class="dashicons dashicons-wordpress-alt"></span>Hook: ' . $hook_name . '</strong>', $sources_column );

		// Content gets a translated name.
		$error_summary['sources_with_invalid_output'] = [ 'hook' => 'the_content' ];
		$sources_column                               = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$this->assertEquals( '<strong class="source"><span class="dashicons dashicons-edit"></span>Content</strong>', $sources_column );

		// Blocks are listed separately, overriding Content.
		$error_summary['sources_with_invalid_output'] = [
			'hook'   => 'the_content',
			'blocks' => [ 'core/html' ],
		];
		$sources_column                               = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$this->assertEquals( '<strong class="source"><span class="dashicons dashicons-edit"></span>Custom HTML</strong>', $sources_column );

		// If there's no source in 'sources_with_invalid_output', this should output the theme name.
		update_post_meta( $post_id, '_amp_validated_environment', [ 'theme' => $theme_name ] );
		$error_summary['sources_with_invalid_output'] = [];
		$sources_column                               = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_sources_column' ], [ $error_summary, $post_id ] );
		$this->assertEquals( '<div class="source"><span class="dashicons dashicons-admin-appearance"></span>' . $theme_name . ' (?)</div>', $sources_column );
	}

	/**
	 * Test for filter_bulk_actions()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::filter_bulk_actions()
	 */
	public function test_filter_bulk_actions() {
		$initial_action = [
			'edit'   => 'Edit',
			'trash'  => 'Trash',
			'delete' => 'Delete',
		];
		$actions        = AMP_Validated_URL_Post_Type::filter_bulk_actions( $initial_action );
		$this->assertArrayNotHasKey( 'edit', $actions );
		$this->assertEquals( 'Recheck', $actions[ AMP_Validated_URL_Post_Type::BULK_VALIDATE_ACTION ] );
		$this->assertArrayNotHasKey( 'trash', $actions );
		$this->assertEquals( 'Forget', $actions['delete'] );
	}

	/**
	 * Test for handle_bulk_action()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::handle_bulk_action()
	 */
	public function test_handle_bulk_action() {
		$this->accept_sanitization_by_default( false );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		add_theme_support( AMP_Theme_Support::SLUG, [ AMP_Theme_Support::PAIRED_FLAG => true ] );
		AMP_Validation_Manager::init();

		$invalid_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'foo' ],
			],
			home_url( '/' )
		);

		$initial_redirect = admin_url( 'plugins.php' );
		$items            = [ $invalid_post_id ];
		$urls_tested      = (string) count( $items );

		$_GET[ AMP_Validated_URL_Post_Type::URLS_TESTED ] = $urls_tested;

		// The action isn't correct, so the callback should return the URL unchanged.
		$this->assertEquals( $initial_redirect, AMP_Validated_URL_Post_Type::handle_bulk_action( $initial_redirect, 'trash', $items ) );

		$that   = $this;
		$filter = static function() use ( $that ) {
			return [
				'body' => wp_json_encode(
					[
						'results' => array_map(
							static function( $error ) {
								return array_merge(
									compact( 'error' ),
									[ 'sanitized' => false ]
								);
							},
							$that->get_mock_errors()
						),
					]
				),
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );
		$this->assertEquals(
			add_query_arg(
				[
					AMP_Validated_URL_Post_Type::URLS_TESTED      => $urls_tested,
					AMP_Validated_URL_Post_Type::REMAINING_ERRORS => count( $items ),
				],
				$initial_redirect
			),
			AMP_Validated_URL_Post_Type::handle_bulk_action( $initial_redirect, AMP_Validated_URL_Post_Type::BULK_VALIDATE_ACTION, $items )
		);
		remove_filter( 'pre_http_request', $filter, 10 );

		// Test error scenario.
		add_filter(
			'pre_http_request',
			static function() {
				return [
					'body' => '<html></html>',
				];
			}
		);
		$this->assertContains(
			'amp_validate_error=',
			AMP_Validated_URL_Post_Type::handle_bulk_action( $initial_redirect, AMP_Validated_URL_Post_Type::BULK_VALIDATE_ACTION, $items )
		);
	}

	/**
	 * Test for print_admin_notice()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::print_admin_notice()
	 * @covers \AMP_Validation_Manager::serialize_validation_error_messages()
	 * @covers \AMP_Validation_Manager::unserialize_validation_error_messages()
	 */
	public function test_print_admin_notice() {
		add_theme_support( AMP_Theme_Support::SLUG );
		AMP_Validation_Manager::init();

		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_admin_notice' ] );
		$this->assertEmpty( $output );

		$_GET['post_type'] = 'post';
		$output            = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_admin_notice' ] );
		$this->assertEmpty( $output );

		set_current_screen( 'edit.php' );
		get_current_screen()->post_type = AMP_Validated_URL_Post_Type::POST_TYPE_SLUG;

		$_GET[ AMP_Validated_URL_Post_Type::REMAINING_ERRORS ] = '1';
		$_GET[ AMP_Validated_URL_Post_Type::URLS_TESTED ]      = '1';
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_admin_notice' ] );
		$this->assertContains( 'The rechecked URL still has remaining invalid markup kept.', $output );

		$_GET[ AMP_Validated_URL_Post_Type::URLS_TESTED ] = '2';
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_admin_notice' ] );
		$this->assertContains( 'The rechecked URLs still have remaining invalid markup kept.', $output );

		$_GET[ AMP_Validated_URL_Post_Type::REMAINING_ERRORS ] = '0';
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_admin_notice' ] );
		$this->assertContains( 'The rechecked URLs are free of non-removed invalid markup.', $output );

		$_GET[ AMP_Validated_URL_Post_Type::URLS_TESTED ] = '1';
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_admin_notice' ] );
		$this->assertContains( 'The rechecked URL is free of non-removed invalid markup.', $output );

		$error_message              = 'Something <code>bad</code> happened!';
		$_GET['amp_validate_error'] = AMP_Validation_Manager::serialize_validation_error_messages( [ $error_message ] );
		$output                     = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_admin_notice' ] );
		$this->assertContains( $error_message, $output );

		unset( $GLOBALS['current_screen'] );
	}

	/**
	 * Test for handle_validate_request()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::handle_validate_request()
	 */
	public function test_handle_validate_request() {
		$this->accept_sanitization_by_default( false );
		add_theme_support( AMP_Theme_Support::SLUG, [ AMP_Theme_Support::PAIRED_FLAG => true ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		AMP_Validation_Manager::init();

		$post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'foo' ],
			],
			home_url( '/' )
		);

		$_REQUEST['_wpnonce'] = wp_create_nonce( AMP_Validated_URL_Post_Type::NONCE_ACTION );

		$exception = null;
		add_filter(
			'wp_redirect',
			static function( $url, $status ) {
				throw new Exception( $url, $status );
			},
			10,
			2
		);

		$that   = $this;
		$filter = static function() use ( $that ) {
			return [
				'body' => wp_json_encode(
					[
						'results' => array_map(
							static function( $error ) {
								return array_merge(
									compact( 'error' ),
									[ 'sanitized' => false ]
								);
							},
							$that->get_mock_errors()
						),
					]
				),
			];
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$handle_validate_request = static function() {
			try {
				AMP_Validated_URL_Post_Type::handle_validate_request();
			} catch ( Exception $exception ) {
				return $exception;
			}
			return null;
		};

		// Test validating with missing args.
		$exception = $handle_validate_request();
		$this->assertInstanceOf( 'Exception', $exception );
		$this->assertEquals( 302, $exception->getCode() );
		$this->assertContains(
			'/edit.php?post_type=amp_validated_url&amp_validate_error=',
			$exception->getMessage()
		);
		unset( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Test validating for a non-valid post.
		$_GET['post'] = 1234567890;
		$exception    = $handle_validate_request();
		$this->assertInstanceOf( 'Exception', $exception );
		$this->assertEquals( 302, $exception->getCode() );
		$this->assertContains(
			'/edit.php?post_type=amp_validated_url&amp_validate_error=',
			$exception->getMessage()
		);
		unset( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Test validating for a non-valid post type.
		$_GET['post'] = self::factory()->post->create();
		$exception    = $handle_validate_request();
		$this->assertInstanceOf( 'Exception', $exception );
		$this->assertEquals( 302, $exception->getCode() );
		$this->assertContains(
			'/edit.php?post_type=amp_validated_url&amp_validate_error=',
			$exception->getMessage()
		);
		unset( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Verify that redirect is happening for a successful case.
		$_GET['post'] = $post_id;
		$exception    = $handle_validate_request();
		$this->assertInstanceOf( 'Exception', $exception );
		$this->assertEquals( 302, $exception->getCode() );
		$this->assertContains(
			sprintf( 'post.php?post=%s&action=edit&amp_urls_tested=', $post_id ),
			$exception->getMessage()
		);
		unset( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Test validating by URL.
		$_GET['url'] = home_url( '/' );
		$exception   = $handle_validate_request();
		$this->assertInstanceOf( 'Exception', $exception );
		$this->assertEquals( 302, $exception->getCode() );
		$this->assertStringEndsWith(
			sprintf( 'post.php?post=%s&action=edit&amp_urls_tested=1&amp_remaining_errors=2', $post_id ),
			$exception->getMessage()
		);

		// Test validating by URL which doesn't have a post already.
		$_GET['url'] = home_url( '/new-URL/' );
		$exception   = $handle_validate_request();
		$this->assertInstanceOf( 'Exception', $exception );
		$this->assertEquals( 302, $exception->getCode() );
		$this->assertStringEndsWith(
			'&action=edit&amp_urls_tested=1&amp_remaining_errors=2',
			$exception->getMessage()
		);

		// Test validating a bad URL.
		$_GET['url'] = 'http://badurl.example.com/';
		$exception   = $handle_validate_request();
		$this->assertInstanceOf( 'Exception', $exception );
		$this->assertEquals( 302, $exception->getCode() );
		$this->assertContains(
			'wp-admin/edit.php?post_type=amp_validated_url&amp_validate_error=',
			$exception->getMessage()
		);
	}

	/**
	 * Test for recheck_post()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::recheck_post()
	 */
	public function test_recheck_post() {
		AMP_Validation_Manager::init();

		$r = AMP_Validated_URL_Post_Type::recheck_post( 'nope' );
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertEquals( 'missing_post', $r->get_error_code() );

		$r = AMP_Validated_URL_Post_Type::recheck_post( self::factory()->post->create() );
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertEquals( 'missing_url', $r->get_error_code() );

		$invalid_url_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'foo' ],
			],
			home_url( '/' )
		);
		add_filter(
			'pre_http_request',
			static function() {
				return [
					'body' => wp_json_encode(
						[
							'results' => [
								[
									'sanitized' => false,
									'error'     => [
										'code' => 'bar',
									],
								],
								[
									'sanitized' => false,
									'error'     => [
										'code' => 'baz',
									],
								],
							],
						]
					),
				];
			}
		);

		$r = AMP_Validated_URL_Post_Type::recheck_post( $invalid_url_post_id );
		$this->assertInternalType( 'array', $r );
		$this->assertCount( 2, $r );
		$this->assertEquals( 'bar', $r[0]['error']['code'] );
		$this->assertEquals( 'baz', $r[1]['error']['code'] );

		$errors = AMP_Validated_URL_Post_Type::get_invalid_url_validation_errors( $invalid_url_post_id );
		$this->assertCount( 2, $errors );
		foreach ( $errors as $i => $error ) {
			$this->assertEquals(
				$r[ $i ]['error'],
				$error['data']
			);
		}
	}

	/**
	 * Test for handle_validation_error_status_update()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::handle_validation_error_status_update()
	 */
	public function test_handle_validation_error_status_update() {
		global $post;
		AMP_Validation_Manager::init();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$_REQUEST[ AMP_Validated_URL_Post_Type::UPDATE_POST_TERM_STATUS_ACTION . '_nonce' ] = wp_create_nonce( AMP_Validated_URL_Post_Type::UPDATE_POST_TERM_STATUS_ACTION );
		AMP_Validated_URL_Post_Type::handle_validation_error_status_update(); // No-op since no post.

		$error = [ 'code' => 'foo' ];

		$invalid_url_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[ $error ],
			home_url( '/' )
		);
		add_filter(
			'pre_http_request',
			static function() use ( $error ) {
				return [
					'body' => wp_json_encode(
						[
							'results' => [
								[
									'sanitized' => false,
									'error'     => $error,
								],
							],
						]
					),
				];
			}
		);

		$post = get_post( $invalid_url_post_id );

		$errors = AMP_Validated_URL_Post_Type::get_invalid_url_validation_errors( $invalid_url_post_id );

		$_POST[ AMP_Validation_Manager::VALIDATION_ERROR_TERM_STATUS_QUERY_VAR ] = [
			$errors[0]['term']->slug => AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_ACCEPTED_STATUS,
		];

		add_filter(
			'wp_redirect',
			static function( $url, $status ) {
				throw new Exception( $url, $status );
			},
			10,
			2
		);
		$exception = null;
		try {
			AMP_Validated_URL_Post_Type::handle_validation_error_status_update();
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->assertInstanceOf( 'Exception', $exception );
		$this->assertEquals( 302, $exception->getCode() );
		$this->assertStringEndsWith( 'action=edit&amp_taxonomy_terms_updated=1&amp_remaining_errors=0', $exception->getMessage() );
	}

	/**
	 * Test for enqueue_edit_post_screen_scripts()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::enqueue_edit_post_screen_scripts()
	 */
	public function test_enqueue_edit_post_screen_scripts() {
		wp_enqueue_script( 'autosave' );
		set_current_screen( 'index.php' );
		AMP_Validated_URL_Post_Type::enqueue_edit_post_screen_scripts();
		$this->assertTrue( wp_script_is( 'autosave', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'amp-validated-url-post-edit-screen', 'enqueued' ) );

		global $pagenow;
		$pagenow = 'post.php';
		set_current_screen( AMP_Validated_URL_Post_Type::POST_TYPE_SLUG );
		AMP_Validated_URL_Post_Type::enqueue_edit_post_screen_scripts();
		$this->assertFalse( wp_script_is( 'autosave', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'amp-validated-url-post-edit-screen', 'enqueued' ) );
		$pagenow = null;
	}

	/**
	 * Test render_link_to_error_index_screen.
	 *
	 * @covers \AMP_Validated_URL_Post_Type::render_link_to_error_index_screen()
	 */
	public function test_render_link_to_error_index_screen() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		global $current_screen;
		set_current_screen( 'index.php' );
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_link_to_error_index_screen' ] );
		$this->assertEmpty( $output );

		set_current_screen( 'edit.php' );
		$current_screen->post_type = AMP_Validated_URL_Post_Type::POST_TYPE_SLUG;
		$output                    = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_link_to_error_index_screen' ] );
		$this->assertContains( 'View Error Index', $output );
	}

	/**
	 * Test for add_meta_boxes()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::add_meta_boxes()
	 */
	public function test_add_meta_boxes() {
		global $wp_meta_boxes;
		AMP_Validated_URL_Post_Type::add_meta_boxes();
		$side_meta_box = $wp_meta_boxes[ AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ]['side']['default'][ AMP_Validated_URL_Post_Type::STATUS_META_BOX ];
		$this->assertEquals( AMP_Validated_URL_Post_Type::STATUS_META_BOX, $side_meta_box['id'] );
		$this->assertEquals( 'Status', $side_meta_box['title'] );
		$this->assertEquals(
			[
				self::TESTED_CLASS,
				'print_status_meta_box',
			],
			$side_meta_box['callback']
		);
		$this->assertEquals(
			[ '__back_compat_meta_box' => true ],
			$side_meta_box['args']
		);

		$contexts = $wp_meta_boxes[ AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ]['side'];
		foreach ( $contexts as $context ) {
			$this->assertFalse( $context['submitdiv'] );
		}
	}

	/**
	 * Test for get_terms_per_page()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::get_terms_per_page()
	 */
	public function test_get_terms_per_page() {
		$initial_counts = [ 0, 22, 1000 ];

		// If 'post.php' === $pagenow, this method should return the same value, no matter what argument is passed to it.
		$GLOBALS['pagenow'] = 'post.php';
		foreach ( $initial_counts as $initial_count ) {
			$this->assertEquals(
				PHP_INT_MAX,
				AMP_Validated_URL_Post_Type::get_terms_per_page( $initial_count )
			);
		}

		// If 'post.php' !== $pagenow, this method should return the same value that is passed to it.
		$GLOBALS['pagenow'] = 'edit-tags.php';
		foreach ( $initial_counts as $initial_count ) {
			$this->assertEquals(
				$initial_count,
				AMP_Validated_URL_Post_Type::get_terms_per_page( $initial_count )
			);
		}
	}

	/**
	 * Test for add_taxonomy()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::add_taxonomy()
	 */
	public function test_add_taxonomy() {
		// The 'pagenow' value is incorrect, so this should not add the taxonomy.
		$GLOBALS['pagenow'] = 'edit.php';
		AMP_Validated_URL_Post_Type::add_taxonomy();
		$this->assertFalse( isset( $_REQUEST['taxonomy'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Though the 'pagenow' value is correct, the $_REQUEST['post'] is not set, and this should not add the taxonomy.
		$GLOBALS['pagenow'] = 'post.php';
		AMP_Validated_URL_Post_Type::add_taxonomy();
		$this->assertFalse( isset( $_REQUEST['taxonomy'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Though the $_REQUEST['post'] is set, it is for a post of the wrong type.
		$wrong_post_type  = self::factory()->post->create();
		$_REQUEST['post'] = $wrong_post_type;
		AMP_Validated_URL_Post_Type::add_taxonomy();
		$this->assertFalse( isset( $_REQUEST['taxonomy'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Now that the post type is correct, this should add the taxonomy to $_REQUEST.
		$correct_post_type = self::factory()->post->create( [ 'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ] );
		$_REQUEST['post']  = $correct_post_type;
		AMP_Validated_URL_Post_Type::add_taxonomy();
		$this->assertEquals( AMP_Validation_Error_Taxonomy::TAXONOMY_SLUG, $_REQUEST['taxonomy'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Test for print_status_meta_box()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::print_status_meta_box()
	 */
	public function test_print_status_meta_box() {
		AMP_Validation_Manager::init();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$invalid_url_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'foo' ],
			],
			home_url( '/' )
		);

		$post_storing_error = get_post( $invalid_url_post_id );

		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_status_meta_box' ], [ get_post( $invalid_url_post_id ) ] );

		$this->assertContains( date_i18n( 'M j, Y @ H:i', strtotime( $post_storing_error->post_date ) ), $output );
		$this->assertContains( 'Last checked:', $output );
		$this->assertContains( 'Forget', $output );
		$this->assertContains( esc_url( get_delete_post_link( $post_storing_error->ID, '', true ) ), $output );
		$this->assertContains( 'misc-pub-section', $output );
	}

	/**
	 * Test for render_single_url_list_table()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::render_single_url_list_table()
	 */
	public function test_render_single_url_list_table() {
		AMP_Validation_Error_Taxonomy::register();
		$post_correct_post_type = self::factory()->post->create_and_get( [ 'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ] );
		$post_wrong_post_type   = self::factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		$GLOBALS['hook_suffix'] = 'post.php';
		$this->go_to( admin_url( 'post.php' ) );
		set_current_screen( 'post.php' );
		$GLOBALS['current_screen']->taxonomy = AMP_Validation_Error_Taxonomy::TAXONOMY_SLUG;

		// If the post type is wrong, so the conditional should be false, and this should not echo anything.
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_single_url_list_table' ], [ $post_wrong_post_type ] );
		$this->assertEmpty( $output );

		// Now that the current user has permissions, this should output the correct markup.
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_single_url_list_table' ], [ $post_correct_post_type ] );
		$this->assertContains( '<form class="search-form wp-clearfix" method="get">', $output );
		$this->assertContains( '<div id="accept-reject-buttons" class="hidden">', $output );
		$this->assertContains( '<button type="button" class="button action accept">', $output );
		$this->assertContains( '<button type="button" class="button action reject">', $output );
	}

	/**
	 * Test for print_url_as_title()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::print_url_as_title()
	 */
	public function test_print_url_as_title() {
		$post_wrong_post_type = self::factory()->post->create_and_get();

		// The $post has the wrong post type, so the method should exit without echoing anything.
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_url_as_title' ], [ $post_wrong_post_type ] );
		$this->assertEmpty( $output );

		// The post type is correct, but it doesn't have a validation URL associated with it, so this shouldn't output anything.
		$post_correct_post_type = self::factory()->post->create_and_get(
			[
				'post-type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG,
			]
		);
		$output                 = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_url_as_title' ], [ $post_correct_post_type ] );
		$this->assertEmpty( $output );

		// The post has the correct type and a validation URL in the title, so this should output markup.
		$post_correct_post_type = self::factory()->post->create_and_get(
			[
				'post_type'  => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG,
				'post_title' => home_url(),
			]
		);
		$output                 = get_echo( [ 'AMP_Validated_URL_Post_Type', 'print_url_as_title' ], [ $post_correct_post_type ] );
		$this->assertContains( '<h2 class="amp-validated-url">', $output );
		$this->assertContains( home_url(), $output );
	}

	/**
	 * Test for filter_the_title_in_post_list_table()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::filter_the_title_in_post_list_table()
	 */
	public function test_filter_the_title_in_post_list_table() {
		global $current_screen;
		$post  = self::factory()->post->create_and_get();
		$title = 'https://example.com/baz';
		set_current_screen( 'front' );

		// The first conditional isn't true yet, so $title should be unchanged.
		$this->assertEquals( $title, AMP_Validated_URL_Post_Type::filter_the_title_in_post_list_table( $title, $post->ID ) );

		/*
		 * The first conditional still isn't true yet, as the $post->post_type isn't correct.
		 * So this should again return $ttile unchanged.
		 */
		set_current_screen( 'edit.php' );
		$current_screen->post_type = AMP_Validated_URL_Post_Type::POST_TYPE_SLUG;
		$this->assertEquals( $title, AMP_Validated_URL_Post_Type::filter_the_title_in_post_list_table( $title, $post->ID ) );

		// The conditional should be true, and this should return the filtered $title.
		$post_correct_post_type = self::factory()->post->create_and_get(
			[
				'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG,
			]
		);
		$this->assertEquals( '/baz', AMP_Validated_URL_Post_Type::filter_the_title_in_post_list_table( $title, $post_correct_post_type ) );
	}

	/**
	 * Test render_post_filters.
	 *
	 * @covers \AMP_Validated_URL_Post_Type::render_post_filters()
	 */
	public function test_render_post_filters() {
		set_current_screen( 'edit.php' );
		AMP_Validated_URL_Post_Type::register();
		AMP_Validation_Error_Taxonomy::register();

		$number_of_new_errors = 20;
		$number_of_rejected   = 15;
		$number_of_accepted   = 5;

		for ( $i = 0; $i < 40; $i++ ) {
			$invalid_url_post      = self::factory()->post->create( [ 'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ] );
			$validation_error_term = self::factory()->term->create(
				[
					'taxonomy'    => AMP_Validation_Error_Taxonomy::TAXONOMY_SLUG,
					'description' => wp_json_encode( array_merge( [ 'code' => 'test' ], compact( 'i' ) ) ),
				]
			);
			if ( $i < 9 ) {
				$status = AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_REJECTED_STATUS;
			} elseif ( $i < 20 ) {
				$status = AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_ACCEPTED_STATUS;
			} elseif ( $i < 35 ) {
				$status = AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_REJECTED_STATUS;
			} else {
				$status = AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACK_ACCEPTED_STATUS;
			}
			wp_update_term(
				$validation_error_term,
				AMP_Validation_Error_Taxonomy::TAXONOMY_SLUG,
				[
					'term_group' => $status,
				]
			);

			// Associate the validation error term with a URL.
			wp_set_post_terms(
				$invalid_url_post,
				[ $validation_error_term ],
				AMP_Validation_Error_Taxonomy::TAXONOMY_SLUG
			);

		}

		$correct_post_type             = AMP_Validated_URL_Post_Type::POST_TYPE_SLUG;
		$wrong_post_type               = 'page';
		$correct_which_second_argument = 'top';
		$wrong_which_second_argument   = 'bottom';

		// This has an incorrect post type as the first argument, so it should not output anything.
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_post_filters' ], [ $wrong_post_type, $correct_which_second_argument ] );
		$this->assertEmpty( $output );

		// This has an incorrect second argument, so again it should not output anything.
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_post_filters' ], [ $correct_post_type, $wrong_which_second_argument ] );
		$this->assertEmpty( $output );

		// This is now on the invalid URL post type edit.php screen, so it should output a <select> element.
		$output = get_echo( [ 'AMP_Validated_URL_Post_Type', 'render_post_filters' ], [ $correct_post_type, $correct_which_second_argument ] );
		$this->assertContains(
			sprintf( 'With new errors <span class="count">(%d)</span>', $number_of_new_errors ),
			$output
		);
		$this->assertContains(
			sprintf( 'With kept markup <span class="count">(%d)</span>', $number_of_rejected ),
			$output
		);
		$this->assertContains(
			sprintf( 'With removed markup <span class="count">(%d)</span>', $number_of_accepted ),
			$output
		);
	}

	/**
	 * Test for get_recheck_url()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::get_recheck_url()
	 */
	public function test_get_recheck_url() {
		AMP_Validation_Manager::init();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id = AMP_Validated_URL_Post_Type::store_validation_errors( $this->get_mock_errors(), home_url( '/' ) );
		$link    = AMP_Validated_URL_Post_Type::get_recheck_url( get_post( $post_id ) );
		$this->assertContains( AMP_Validated_URL_Post_Type::VALIDATE_ACTION, $link );
		$this->assertContains( wp_create_nonce( AMP_Validated_URL_Post_Type::NONCE_ACTION ), $link );
	}

	/**
	 * Test for filter_dashboard_glance_items()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::filter_dashboard_glance_items()
	 */
	public function test_filter_dashboard_glance_items() {

		// There are no validation errors, so this should return the argument unchanged.
		$this->assertEmpty( AMP_Validated_URL_Post_Type::filter_dashboard_glance_items( [] ) );

		// Create validation errors, so that the method returns items.
		$post_id = self::factory()->post->create();
		AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'accepted' ],
				[ 'code' => 'rejected' ],
				[ 'code' => 'new' ],
			],
			get_permalink( $post_id )
		);
		$items = AMP_Validated_URL_Post_Type::filter_dashboard_glance_items( [] );
		$this->assertContains( '1 URL w/ new AMP errors', $items[0] );
		$this->assertContains( AMP_Validated_URL_Post_Type::POST_TYPE_SLUG, $items[0] );
		$this->assertContains( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_STATUS_QUERY_VAR, $items[0] );
	}

	/**
	 * Test for get_validated_url_title()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::get_validated_url_title()
	 */
	public function test_get_validated_url_title() {
		$meta_key               = '_amp_queried_object';
		$test_post              = self::factory()->post->create_and_get();
		$amp_validated_url_post = self::factory()->post->create_and_get( [ 'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG ] );

		update_post_meta(
			$amp_validated_url_post->ID,
			$meta_key,
			[
				'type' => 'post',
				'id'   => $test_post->ID,
			]
		);
		$this->assertEquals(
			$test_post->post_title,
			AMP_Validated_URL_Post_Type::get_validated_url_title( $amp_validated_url_post )
		);

		// If the URL with validation error(s) is a term, this should return the term name.
		$term = self::factory()->term->create_and_get();
		update_post_meta(
			$amp_validated_url_post->ID,
			$meta_key,
			[
				'type' => 'term',
				'id'   => $term->term_id,
			]
		);
		$this->assertEquals(
			$term->name,
			AMP_Validated_URL_Post_Type::get_validated_url_title( $amp_validated_url_post )
		);

		// If the URL with validation error(s) is for a user (author), this should return the author's name.
		$user = self::factory()->user->create_and_get();
		update_post_meta(
			$amp_validated_url_post->ID,
			$meta_key,
			[
				'type' => 'user',
				'id'   => $user->ID,
			]
		);
		$this->assertEquals(
			$user->display_name,
			AMP_Validated_URL_Post_Type::get_validated_url_title( $amp_validated_url_post )
		);
	}

	/**
	 * Test for filter_post_row_actions()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::filter_post_row_actions()
	 */
	public function test_filter_post_row_actions() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		add_theme_support( AMP_Theme_Support::SLUG );
		AMP_Validation_Manager::init();

		$validated_url   = home_url( '/' );
		$invalid_post_id = AMP_Validated_URL_Post_Type::store_validation_errors(
			[
				[ 'code' => 'foo' ],
			],
			$validated_url
		);

		$initial_actions = [
			'trash' => sprintf( '<a href="%s" class="submitdelete" aria-label="Trash &#8220;%s&#8221;">Trash</a>', get_delete_post_link( $invalid_post_id ), $validated_url ),
		];

		$this->assertEquals( $initial_actions, AMP_Validated_URL_Post_Type::filter_post_row_actions( $initial_actions, self::factory()->post->create_and_get() ) );

		$actions = AMP_Validated_URL_Post_Type::filter_post_row_actions( $initial_actions, get_post( $invalid_post_id ) );
		$this->assertArrayNotHasKey( 'inline hide-if-no-js', $actions );
		$this->assertArrayHasKey( 'view', $actions );
		$this->assertArrayHasKey( AMP_Validated_URL_Post_Type::VALIDATE_ACTION, $actions );
		$this->assertArrayNotHasKey( 'trash', $actions );
		$this->assertArrayHasKey( 'delete', $actions );
		$this->assertNotContains( 'Trash', $actions['delete'] );
		$this->assertContains( 'Forget', $actions['delete'] );

		$this->assertEquals( [], AMP_Validated_URL_Post_Type::filter_post_row_actions( [], null ) );

		$actions = [
			'trash'  => '',
			'delete' => '',
		];

		$post = self::factory()->post->create_and_get(
			[
				'post_type' => AMP_Validated_URL_Post_Type::POST_TYPE_SLUG,
				'title'     => 'My Post',
			]
		);

		$filtered_actions = AMP_Validated_URL_Post_Type::filter_post_row_actions( $actions, $post );

		$this->assertArrayNotHasKey( 'trash', $filtered_actions );
		$this->assertArrayHasKey( 'delete', $filtered_actions );
		$this->assertContains( 'Forget</a>', $filtered_actions['delete'] );

	}

	/**
	 * Test for filter_table_views()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::filter_table_views()
	 */
	public function test_filter_table_views() {
		$this->assertEquals( [], AMP_Validated_URL_Post_Type::filter_table_views( [] ) );

		$views = [
			'trash' => 'Trash',
		];

		$filtered_views = AMP_Validated_URL_Post_Type::filter_table_views( $views );

		$this->assertEquals( 'Forgotten', $filtered_views['trash'] );
	}

	/**
	 * Test for filter_bulk_post_updated_messages()
	 *
	 * @covers \AMP_Validated_URL_Post_Type::filter_bulk_post_updated_messages()
	 */
	public function test_filter_bulk_post_updated_messages() {
		set_current_screen( 'index.php' );

		$this->assertEquals( [], AMP_Validated_URL_Post_Type::filter_bulk_post_updated_messages( [], [] ) );

		set_current_screen( 'edit.php' );
		get_current_screen()->id = sprintf( 'edit-%s', AMP_Validated_URL_Post_Type::POST_TYPE_SLUG );

		$messages = [
			'post' => [],
		];

		$filtered_messages = AMP_Validated_URL_Post_Type::filter_bulk_post_updated_messages(
			$messages,
			[
				'deleted'   => 1,
				'trashed'   => 99,
				'untrashed' => 99,
			]
		);

		$this->assertEquals( '%s validated URL forgotten.', $filtered_messages['post']['deleted'] );
		$this->assertEquals( '%s validated URLs forgotten.', $filtered_messages['post']['trashed'] );
		$this->assertEquals( '%s validated URLs unforgotten.', $filtered_messages['post']['untrashed'] );
	}


	/**
	 * Gets mock errors for tests.
	 *
	 * @return array $errors[][] {
	 *     The data of the validation errors.
	 *
	 *     @type string    $code        Error code.
	 *     @type string    $node_name   Name of removed node.
	 *     @type string    $parent_name Name of parent node.
	 *     @type array[][] $sources     Source data, including plugins and themes.
	 * }
	 */
	public function get_mock_errors() {
		return [
			[
				'code'            => AMP_Tag_And_Attribute_Sanitizer::DISALLOWED_TAG,
				'node_name'       => 'script',
				'parent_name'     => 'div',
				'node_attributes' => [],
				'sources'         => [
					[
						'type' => 'plugin',
						'name' => 'amp',
					],
				],
			],
			[
				'code'               => AMP_Tag_And_Attribute_Sanitizer::DISALLOWED_ATTR,
				'node_name'          => 'onclick',
				'parent_name'        => 'div',
				'element_attributes' => [
					'onclick' => '',
				],
				'sources'            => [
					[
						'type' => 'plugin',
						'name' => 'amp',
					],
				],
			],
		];
	}
}
