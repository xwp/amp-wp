/**
 * WordPress dependencies
 */
import { visitAdminPage } from '@wordpress/e2e-test-utils';

describe( 'AMP analytics options', () => {
	beforeEach( async () => {
		await visitAdminPage( 'admin.php', 'page=amp-options' );
	} );

	it( 'allows adding and deleting entries', async () => {
		await expect( page ).toClick( '#analytics-options-drawer .components-panel__body-toggle' );
		await expect( page ).not.toMatchElement( '.amp-analytics-entry' );

		// Add entry.
		await expect( page ).toClick( '#amp-analytics-add-entry' );
		await expect( '.amp-analytics-entry' ).countToBe( 1 );
		await expect( page ).toFill( '#amp-analytics-entry-1 input[type="text"]', 'googleanalytics' );

		// Add second entry.
		await expect( page ).toClick( '#amp-analytics-add-entry' );
		await expect( '.amp-analytics-entry' ).countToBe( 2 );
		await expect( page ).toFill( '#amp-analytics-entry-2 input[type="text"]', 'googleanalytics-2' );

		// Save.
		await expect( page ).toClick( '.settings-footer button[type="submit"]' );

		// Wait for the success notice. Note: This might not be reliable and should be removed if it causes problems.
		await page.waitFor( 2000 );
		await expect( page ).toMatchElement( '.amp .amp-save-success-notice.amp-notice' );

		// Delete entries.
		await expect( page ).toClick( '.amp-analytics__delete-button' );
		await expect( page ).toClick( '.amp-analytics__delete-button' );

		await expect( page ).not.toMatchElement( '.amp-analytics-entry' );

		// Save.
		await expect( page ).toClick( '.settings-footer button[type="submit"]' );
		await expect( page ).toMatchElement( '.amp .amp-save-success-notice.amp-notice' );
	} );
} );

