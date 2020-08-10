/**
 * WordPress dependencies
 */
import { visitAdminPage } from '@wordpress/e2e-test-utils';

export const NEXT_BUTTON_SELECTOR = '#next-button';
export const PREV_BUTTON_SELECTOR = '.onboarding-wizard-nav__prev-next button:not(.is-primary)';

export async function goToOnboardingWizard() {
	await visitAdminPage( 'index.php' );
	await expect( page ).not.toMatchElement( '#amp-onboarding-wizard' );
	await visitAdminPage( 'admin.php', 'page=amp-onboarding-wizard' );
	await expect( page ).toMatchElement( '#amp-onboarding-wizard' );
}

export async function clickNextButton() {
	await expect( page ).toClick( `${ NEXT_BUTTON_SELECTOR }:not([disabled])` );
}

export async function clickPrevButton() {
	await expect( page ).toClick( `${ PREV_BUTTON_SELECTOR }:not([disabled])` );
}

export async function moveToTechnicalScreen() {
	await goToOnboardingWizard();
	await clickNextButton();
	await expect( page ).toMatchElement( '.technical-background-option' );
}

export async function moveToTemplateModeScreen( { technical } ) {
	await moveToTechnicalScreen();

	const radioSelector = technical ? '#technical-background-enable' : '#technical-background-disable';
	await expect( page ).toClick( radioSelector );

	await clickNextButton();
	await expect( page ).toMatchElement( '.template-mode-option' );
}

export async function clickMode( mode ) {
	await page.evaluate( ( templateMode ) => {
		const el = document.querySelector( `#template-mode-${ templateMode }` );
		if ( el ) {
			el.scrollIntoView( { block: 'center', inline: 'center' } );
		}
	}, mode );
	await expect( page ).toMatchElement( `#template-mode-${ mode }` );
	await page.click( `#template-mode-${ mode }` );
	await expect( page ).toMatchElement( `#template-mode-${ mode }:checked` );
}

export async function moveToReaderThemesScreen( { technical } ) {
	await moveToTemplateModeScreen( { technical } );
	await clickMode( 'reader' );
	await clickNextButton();
	await page.waitForSelector( '.theme-card' );
}

export async function selectReaderTheme( theme = 'legacy' ) {
	const selector = `[for="theme-card__${ theme }"]`;

	await page.waitForSelector( selector );
	await page.$eval( selector, ( el ) => el.click() );
}

export async function moveToSummaryScreen( { technical = true, mode, readerTheme = 'legacy' } ) {
	if ( mode === 'reader' ) {
		await moveToReaderThemesScreen( { technical } );
		await selectReaderTheme( readerTheme );
	} else {
		await moveToTemplateModeScreen( { technical } );
		await clickMode( mode );
	}

	await clickNextButton();

	await page.waitForSelector( '.summary' );
}

export async function moveToDoneScreen( { technical = true, mode, readerTheme = 'legacy', mobileRedirect = true } ) {
	await moveToSummaryScreen( { technical, mode, readerTheme, mobileRedirect } );

	if ( 'standard' !== mode ) {
		await page.waitForSelector( '.amp-setting-toggle input' );

		const selector = '.amp-setting-toggle .components-form-toggle.is-checked';
		const checkedMobileRedirect = await page.waitForSelector( selector );

		if ( checkedMobileRedirect && false === mobileRedirect ) {
			const labelSelector = `${ selector } + label`;
			await page.evaluate( ( selectorLabel ) => {
				document.querySelector( selectorLabel ).scrollIntoView();
			}, labelSelector );
			await expect( page ).toClick( labelSelector );
			await page.waitForSelector( '.amp-setting-toggle .components-form-toggle:not(.is-checked)' );
		} else if ( ! checkedMobileRedirect && true === mobileRedirect ) {
			await expect( page ).toClick( selector );
			await page.waitForSelector( selector );
		}
	}

	await clickNextButton();
	await page.waitFor( 1000 );
	await page.waitForSelector( '.done__preview-container' );
}

export async function completeWizard( { technical = true, mode, readerTheme = 'legacy', mobileRedirect = true } ) {
	await moveToDoneScreen( { technical, mode, readerTheme, mobileRedirect } );
	if ( 'reader' === mode ) {
		await visitAdminPage( 'admin.php', 'page=amp-options' );
	} else {
		await expect( page ).toClick( '#next-button' );
	}
	await page.waitForSelector( '#amp-settings' );
	await expect( page ).toMatchElement( '#amp-settings' );
}

export async function testCloseButton( { exists = true } ) {
	if ( exists ) {
		await expect( page ).toMatchElement( 'a', { text: 'Close' } );
	} else {
		await expect( page ).not.toMatchElement( 'a', { text: 'Close' } );
	}
}

export async function testPreviousButton( { exists = true, disabled = false } ) {
	if ( exists ) {
		await expect( page ).toMatchElement( `button${ disabled ? '[disabled]' : '' }`, { text: 'Previous' } );
	} else {
		await expect( page ).not.toMatchElement( `button${ disabled ? '[disabled]' : '' }`, { text: 'Previous' } );
	}
}

export function testNextButton( { element = 'button', text, disabled = false } ) {
	expect( page ).toMatchElement( `${ element }${ disabled ? '[disabled]' : '' }`, { text } );
}

export function testTitle( { text, element = 'h1' } ) {
	expect( page ).toMatchElement( element, { text } );
}

/**
 * Reset plugin configuration.
 */
export async function cleanUpSettings() {
	await visitAdminPage( 'admin.php', 'page=amp-options' );
	await page.waitForSelector( '.settings-footer' );
	await page.evaluate( async () => {
		await Promise.all( [
			wp.apiFetch( { path: '/wp/v2/users/me', method: 'POST', data: { amp_dev_tools_enabled: true } } ),
			wp.apiFetch( { path: '/amp/v1/options', method: 'POST', data: {
				mobile_redirect: false,
				reader_theme: 'legacy',
				theme_support: 'reader',
				plugin_configured: false,
			} } ),
		],
		);
	} );
}
