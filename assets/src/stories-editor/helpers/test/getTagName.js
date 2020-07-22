/**
 * Internal dependencies
 */
import { getTagName } from '../maybeSetTagName';

const mockGetBlocksByClientId = jest.fn( () => [] );
const mockGetBlockOrder = jest.fn( () => [] );
const mockGetBlock = jest.fn();
const mockUpdateBlockAttributes = jest.fn();

jest.mock( '@wordpress/data', () => {
	return {
		select: () => ( {
			getBlocksByClientId: ( ...args ) => mockGetBlocksByClientId( ...args ),
			getBlockOrder: ( ...args ) => mockGetBlockOrder( ...args ),
			getBlock: ( ...args ) => mockGetBlock( ...args ),
		} ),

		dispatch: () => ( {
			updateBlockAttributes: ( ...args ) => mockUpdateBlockAttributes( ...args ),
		} ),
	};
} );

describe( 'getTagName', () => {
	it( 'should return type if explicitly set', () => {
		expect( getTagName( { type: 'h2' } ) ).toBe( 'h2' );
	} );

	it( 'should return p if block offset is below threshold', () => {
		expect( getTagName( { positionTop: 200 } ) ).toBe( 'p' );
	} );

	it( 'should return heading if text is large enough', () => {
		expect( getTagName( { fontSize: 'huge' }, true ) ).toBe( 'h1' );
		expect( getTagName( { fontSize: 'huge' }, false ) ).toBe( 'h2' );
		expect( getTagName( { customFontSize: 50 }, true ) ).toBe( 'h1' );
		expect( getTagName( { customFontSize: 50 }, false ) ).toBe( 'h2' );
		expect( getTagName( { fontSize: 'large' }, true ) ).toBe( 'h2' );
		expect( getTagName( { customFontSize: 30 }, true ) ).toBe( 'h2' );
		expect( getTagName( { customFontSize: 30 }, false ) ).toBe( 'h2' );
	} );

	it( 'should return heading if text is short enough', () => {
		expect( getTagName( { content: 'Hello World' }, true ) ).toBe( 'h1' );
		expect( getTagName( { content: 'Hello World' }, false ) ).toBe( 'h2' );
		expect( getTagName( { content: 'This heading is a bit longer' }, true ) ).toBe( 'h2' );
		expect( getTagName( { content: 'This heading is a bit longer' }, false ) ).toBe( 'h2' );
	} );

	it( 'should return paragraph if text is long enough', () => {
		expect( getTagName( { content: 'This content is way longer than what is normal for a heading' }, false ) ).toBe( 'p' );
	} );
} );
