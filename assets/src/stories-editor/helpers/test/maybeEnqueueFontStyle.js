/**
 * Internal dependencies
 */
import maybeEnqueueFontStyle from '../maybeEnqueueFontStyle';

describe( 'maybeEnqueueFontStyle', () => {
	it( 'should ignore invalid font name', () => {
		expect( maybeEnqueueFontStyle( undefined ) ).toBeUndefined( );
	} );

	it( 'should ignore missing font name', () => {
		expect( maybeEnqueueFontStyle( 'Tahoma' ) ).toBeUndefined( );
	} );

	it( 'should ignore font without handle', () => {
		expect( maybeEnqueueFontStyle( 'Ubuntu' ) ).toBeUndefined( );
	} );

	it( 'should ignore font without src', () => {
		expect( maybeEnqueueFontStyle( 'Verdana' ) ).toBeUndefined( );
	} );

	it( 'should enqueue requested font only once', () => {
		maybeEnqueueFontStyle( 'Roboto' );
		maybeEnqueueFontStyle( 'Roboto' );

		expect( document.querySelectorAll( '#roboto-font' ) ).toHaveLength( 1 );
		expect( document.querySelector( '#roboto-font' ).getAttribute( 'rel' ) ).toStrictEqual( 'stylesheet' );
		expect( document.querySelector( '#roboto-font' ).getAttribute( 'type' ) ).toStrictEqual( 'text/css' );
		expect( document.querySelector( '#roboto-font' ).getAttribute( 'media' ) ).toStrictEqual( 'all' );
		expect( document.querySelector( '#roboto-font' ).getAttribute( 'crossorigin' ) ).toStrictEqual( 'anonymous' );
	} );
} );
