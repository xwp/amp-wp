/**
 * WordPress dependencies
 */
import { visitAdminPage, insertBlock, createNewPost, searchForBlock } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import {
	activateExperience,
	clickButtonByLabel,
	clickOnMoreMenuItem,
	deactivateExperience,
	openTemplateInserter,
	removeAllBlocks,
	searchForBlock as searchForStoryBlock,
	getBlocksOnPage,
	wpDataSelect,
} from '../../utils';

/**
 * Adds a reusable block.
 */
async function addReusableBlock() {
	await createNewPost();

	const isTopToolbarEnabled = await page.$eval( '.edit-post-layout', ( layout ) => {
		return layout.classList.contains( 'has-fixed-toolbar' );
	} );
	if ( ! isTopToolbarEnabled ) {
		await clickOnMoreMenuItem( 'Top Toolbar' );
	}

	await removeAllBlocks();

	// Insert a paragraph block
	await insertBlock( 'Paragraph' );
	await page.keyboard.type( 'Reusable block!' );

	await clickButtonByLabel( 'More options' );

	const convertButton = await page.waitForXPath( '//button[text()="Add to Reusable Blocks"]' );
	await convertButton.click();
	// Wait for the snackbar confirmation of the block creation result.
	await page.waitForSelector( '.components-snackbar__content' );
}

/**
 * Removes all reusable blocks visible on the wp_blocks edit screen.
 */
async function removeAllReusableBlocks() {
	await visitAdminPage( 'edit.php', 'post_type=wp_block' );

	const hasReusableBlocks = ( await page.$x( '//*[@id="doaction"]' ) ).length > 0;

	if ( ! hasReusableBlocks ) {
		return;
	}

	await page.click( '#cb-select-all-1' );
	await page.select( '#bulk-action-selector-top', 'trash' );
	await Promise.all( [
		page.click( '#doaction' ),
		page.waitForNavigation(),
	] );
}

describe( 'Story Templates', () => {
	describe( 'Stories experience disabled', () => {
		it( 'should hide story templates from the reusable blocks management screen', async () => {
			await visitAdminPage( 'edit.php', 'post_type=wp_block' );
			await expect( page ).toMatchElement( '.no-items' );
		} );

		it( 'should hide story templates in the regular block editor', async () => {
			await createNewPost();
			await searchForBlock( 'Template' );

			await expect( page ).toMatchElement( '.block-editor-inserter__no-results' );
		} );

		describe( 'With non-template Reusable block', () => {
			beforeAll( async () => {
				await removeAllReusableBlocks();
				await addReusableBlock();
			} );

			afterAll( async () => {
				await removeAllReusableBlocks();
			} );

			it( 'should display non-template reusable blocks in the reusable blocks management screen', async () => {
				const titleSelector = '.page-title .row-title';

				await visitAdminPage( 'edit.php', 'post_type=wp_block' );
				await page.waitForSelector( titleSelector );

				// Check that it is untitled
				const title = await page.$eval(
					titleSelector,
					( element ) => element.innerText,
				);
				expect( title ).toBe( 'Untitled Reusable Block' );
			} );

			it( 'should display non-template reusable blocks in the regular block editor', async () => {
				await createNewPost();
				await searchForBlock( 'Reusable' );

				await expect( page ).not.toMatchElement( '.block-editor-inserter__no-results' );
			} );
		} );
	} );

	describe( 'Stories experience enabled', () => {
		beforeAll( async () => {
			await activateExperience( 'stories' );
			await removeAllReusableBlocks();
		} );

		afterAll( async () => {
			await deactivateExperience( 'stories' );
			await removeAllReusableBlocks();
		} );

		// @todo Fix unstable test case.
		// eslint-disable-next-line jest/no-disabled-tests
		it.skip( 'should hide story templates from the reusable blocks management screen', async () => {
			await visitAdminPage( 'edit.php', 'post_type=wp_block' );
			await expect( page ).toMatchElement( '.no-items' );
		} );

		it( 'should hide story templates in the regular block editor inserter', async () => {
			await createNewPost();
			await searchForBlock( 'Template' );

			await expect( page ).toMatchElement( '.block-editor-inserter__no-results' );
		} );

		it( 'should hide story templates in the stories editor inserter', async () => {
			await createNewPost( { postType: 'amp_story' } );
			await searchForStoryBlock( 'Template' );

			await expect( page ).toMatchElement( '.block-editor-inserter__no-results' );
		} );

		// Disable reason: see https://github.com/ampproject/amp-wp/issues/3211
		// eslint-disable-next-line jest/no-disabled-tests
		it.skip( 'should load story templates in the stories editor', async () => {
			await createNewPost( { postType: 'amp_story' } );

			await openTemplateInserter();

			const numberOfTemplates = await page.$$eval( '.block-editor-block-preview', ( templates ) => templates.length );
			expect( numberOfTemplates ).toStrictEqual( 11 ); // 10 default templates plus the empty page.
		} );

		it( 'should not load default reusable blocks in the stories editor', async () => {
			await addReusableBlock();
			await createNewPost( { postType: 'amp_story' } );
			await searchForStoryBlock( 'Reusable' );

			await expect( page ).toMatchElement( '.block-editor-inserter__no-results' );
		} );

		// Disable reason: see https://github.com/ampproject/amp-wp/issues/3211
		// eslint-disable-next-line jest/no-disabled-tests
		it.skip( 'should insert the correct blocks and as skeletons when clicking on a template', async () => {
			await createNewPost( { postType: 'amp_story' } );

			await openTemplateInserter();

			const nodes = await page.$x(
				'//*[contains(@class,"block-editor-block-preview")]',
			);

			// Wait until the templates are loaded and blocks accessible.
			await page.waitForSelector( '.block-editor-block-preview .wp-block' );

			// Click on the template including quote and image.
			await nodes[ 3 ].click();

			const numberOfQuotes = await page.$$eval( '.amp-page-active .wp-block-quote', ( elements ) => elements.length );
			expect( numberOfQuotes ).toStrictEqual( 1 );

			const numberOfImages = await page.$$eval( '.amp-page-active .wp-block-image', ( elements ) => elements.length );
			expect( numberOfImages ).toStrictEqual( 1 );

			// Verify that only 2 child blocks were added.
			const numberOfBlocks = await page.$$eval( '.amp-page-active .wp-block.editor-block-list__block', ( elements ) => elements.length );
			expect( numberOfBlocks ).toStrictEqual( 2 );

			// Verify that the image is added empty, as placeholder.
			const placeholderImages = await page.$$eval( '.amp-page-active .wp-block-image.block-editor-media-placeholder', ( elements ) => elements.length );
			expect( placeholderImages ).toStrictEqual( 1 );

			// Verify that the block is not added with style.
			const defaultStyledQuote = await page.$$eval( '.amp-page-active .wp-block-quote.is-style-white', ( elements ) => elements.length );
			expect( defaultStyledQuote ).toStrictEqual( 0 );
		} );

		// Disable reason: see https://github.com/ampproject/amp-wp/issues/3211
		// eslint-disable-next-line jest/no-disabled-tests
		it.skip( 'should contain expected content in the template preview', async () => {
			await createNewPost( { postType: 'amp_story' } );

			await openTemplateInserter();

			// Wait until the templates are loaded and blocks accessible.
			await page.waitForSelector( '.block-editor-block-preview .wp-block' );

			const templateContents = await page.evaluate( () => {
				const contents = [];
				const previews = document.querySelectorAll( '.block-editor-block-preview .block-editor-inner-blocks.has-overlay' );
				previews.forEach( function( preview, index ) {
					contents[ index ] = preview.innerHTML;
				} );
				return contents;
			} );

			// Travel Tip template.
			expect( templateContents[ 0 ] ).toContain( 'This template is great for sharing tips' );
			// Quote template.
			expect( templateContents[ 1 ] ).toContain( '<p>Everyone soon or late comes round Rome</p>' );
			// Travel CTA template.
			expect( templateContents[ 2 ] ).toContain( '<strong>Show packing tips</strong>' );
			// Title Page.
			expect( templateContents[ 3 ] ).toContain( 'wp-block-amp-amp-story-post-date has-text-color' );
			// Vertical.
			expect( templateContents[ 4 ] ).toContain( 'Journey into the past' );
			// Fandom Title.
			expect( templateContents[ 5 ] ).toContain( 'SPOILERS ALERT' );
			// Fandom CTA.
			expect( templateContents[ 6 ] ).toContain( '<strong>S</strong>tart Quiz' );
			// Fandom Fact.
			expect( templateContents[ 7 ] ).toContain( 'Robb Start made Jon his heir<br>(in books)' );
			// Fandom Fact Text.
			expect( templateContents[ 8 ] ).toContain( 'One of the biggest things missing from the show is the fact that before his death, Robb Start legitimizes Jon Snow as a Stark and makes him his heir.' );
			// Fandom Intro
			expect( templateContents[ 9 ] ).toContain( 'got-logo.png' );
		} );

		// @see https://github.com/ampproject/amp-wp/issues/3211
		it( 'should directly insert a new blank page', async () => {
			await createNewPost( { postType: 'amp_story' } );

			const blockOrder = await wpDataSelect( 'core/block-editor', 'getBlockOrder' );
			await page.click( '.block-editor-inserter .editor-inserter__amp-inserter' );
			await expect( page ).toMatchElement( '.amp-story-editor-carousel-navigation button:not(disabled)[aria-label="Previous Page"]' );
			await expect( page ).toMatchElement( '.amp-story-editor-carousel-navigation button[disabled][aria-label="Next Page"]' );

			const newBlockOrder = await wpDataSelect( 'core/block-editor', 'getBlockOrder' );
			expect( newBlockOrder ).toHaveLength( blockOrder.length + 1 );
			expect( await getBlocksOnPage() ).toHaveLength( 0 );
		} );
	} );
} );
