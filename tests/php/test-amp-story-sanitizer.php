<?php
/**
 * Tests for AMP stories sanitization.
 *
 * @package AMP
 */

// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned

/**
 * Class AMP_Story_Sanitizer_Test
 *
 * @group amp-comments
 * @group amp-form
 */
class AMP_Story_Sanitizer_Test extends WP_UnitTestCase {

	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
		$this->go_to( '/current-page/' );
	}

	/**
	 * Data strings for testing converter.
	 *
	 * @return array
	 */
	public function get_data() {
		return [
			'story_without_cta' => [
				'<amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer></amp-story-page>',
				null, // Same.
			],
			'story_with_cta_on_first_page' => [
				'<amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer><amp-story-cta-layer><a href="">Foo</a></amp-story-cta-layer></amp-story-page>',
				'<amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer></amp-story-page>',
			],
			'story_with_cta_on_second_page' => [
				'<amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer></amp-story-page><amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer><amp-story-cta-layer><a href="">Foo</a></amp-story-cta-layer></amp-story-page>',
				null, // Same.
			],
			'story_with_multiple_cta_on_second_page' => [
				'<amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer></amp-story-page><amp-story-page><amp-story-grid-layer></amp-story-grid-layer><amp-story-cta-layer><a href="">Foo</a></amp-story-cta-layer><amp-story-cta-layer><a href="">Foo</a></amp-story-cta-layer></amp-story-page>',
				'<amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer></amp-story-page><amp-story-page><amp-story-grid-layer></amp-story-grid-layer><amp-story-cta-layer><a href="">Foo</a></amp-story-cta-layer></amp-story-page>',
			],
			'story_with_invalid_root_elements' => [
				'<p>Word count: 4</p><amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer></amp-story-page><p>Related posts: <a href="https://example.com/"><strong>Example</strong></a></p>',
				'<amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer></amp-story-page>',
			],
			'story_with_invalid_layer_siblings' => [
				'<amp-story-page><p>Before layer</p><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer><p>After layer</p></amp-story-page</p>',
				'<amp-story-page><amp-story-grid-layer><p>Lorem Ipsum Demet Delorit.</p></amp-story-grid-layer></amp-story-page>',
			],
		];
	}

	/**
	 * Test html conversion.
	 *
	 * @param string      $source   The source HTML.
	 * @param string|null $expected The expected HTML after conversion. Null means same as $source.
	 * @dataProvider get_data
	 */
	public function test_converter( $source, $expected = null ) {
		$amp_story_element = '<amp-story standalone title="Test" publisher="Tester" publisher-logo-src="https://example.com/favicons/tester-228x228.png" poster-portrait-src="https://example.com/test.jpg">%s</amp-story>';

		$source = sprintf( $amp_story_element, $source );
		if ( is_null( $expected ) ) {
			$expected = $source;
		} else {
			$expected = sprintf( $amp_story_element, $expected );
		}
		$dom = AMP_DOM_Utils::get_dom_from_content( $source );

		$sanitizer = new AMP_Story_Sanitizer( $dom );
		$sanitizer->sanitize();

		$content = AMP_DOM_Utils::get_content_from_dom( $dom );
		$content = preg_replace( '/(?<=>)\s+(?=<)/', '', $content );

		$this->assertEquals( $expected, $content );
	}
}
