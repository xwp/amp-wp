/**
 * Internal dependencies
 */
import {
	addAnimation,
	changeAnimationType,
	changeAnimationDuration,
	changeAnimationDelay,
	setCurrentPage,
	startReordering,
	movePageToPosition,
	saveOrder,
	resetOrder,
	setCopiedMarkup,
	clearCopiedMarkup,
	playAnimation,
	stopAnimation,
} from '../actions';

describe( 'actions', () => {
	describe( 'addAnimation', () => {
		it( 'should return the ADD_ANIMATION action', () => {
			const page = 'foo';
			const item = 'bar';
			const predecessor = 'baz';

			const result = addAnimation( page, item, predecessor );
			expect( result ).toStrictEqual( {
				type: 'ADD_ANIMATION',
				page,
				item,
				predecessor,
			} );
		} );
	} );

	describe( 'changeAnimationType', () => {
		it( 'should return the CHANGE_ANIMATION_TYPE action', () => {
			const page = 'foo';
			const item = 'bar';
			const animationType = 'drop';

			const result = changeAnimationType( page, item, animationType );
			expect( result ).toStrictEqual( {
				type: 'CHANGE_ANIMATION_TYPE',
				page,
				item,
				animationType,
			} );
		} );
	} );

	describe( 'changeAnimationDuration', () => {
		it( 'should return the CHANGE_ANIMATION_DURATION action', () => {
			const page = 'foo';
			const item = 'bar';
			const duration = 5000;

			const result = changeAnimationDuration( page, item, duration );
			expect( result ).toStrictEqual( {
				type: 'CHANGE_ANIMATION_DURATION',
				page,
				item,
				duration,
			} );
		} );
	} );

	describe( 'changeAnimationDelay', () => {
		it( 'should return the CHANGE_ANIMATION_DELAY action', () => {
			const page = 'foo';
			const item = 'bar';
			const delay = 1000;

			const result = changeAnimationDelay( page, item, delay );
			expect( result ).toStrictEqual( {
				type: 'CHANGE_ANIMATION_DELAY',
				page,
				item,
				delay,
			} );
		} );
	} );

	describe( 'playAnimation', () => {
		it( 'should return the PLAY_ANIMATION action for a given page', () => {
			const page = 'foo';

			const result = playAnimation( page );
			expect( result ).toStrictEqual( {
				type: 'PLAY_ANIMATION',
				page,
				item: undefined,
			} );
		} );

		it( 'should return the PLAY_ANIMATION action for a given page and item', () => {
			const page = 'foo';
			const item = 'bar';

			const result = playAnimation( page, item );
			expect( result ).toStrictEqual( {
				type: 'PLAY_ANIMATION',
				page,
				item,
			} );
		} );
	} );

	describe( 'stopAnimation', () => {
		it( 'should return the STOP_ANIMATION action for a given page', () => {
			const page = 'foo';

			const result = stopAnimation( page );
			expect( result ).toStrictEqual( {
				type: 'STOP_ANIMATION',
				item: undefined,
				page,
			} );
		} );

		it( 'should return the STOP_ANIMATION action for a given page and item', () => {
			const page = 'foo';
			const item = 'bar';

			const result = stopAnimation( page, item );
			expect( result ).toStrictEqual( {
				type: 'STOP_ANIMATION',
				page,
				item,
			} );
		} );
	} );

	describe( 'setCurrentPage', () => {
		it( 'should return the SET_CURRENT_PAGE action', () => {
			const page = 'foo';

			const result = setCurrentPage( page );
			expect( result ).toStrictEqual( {
				type: 'SET_CURRENT_PAGE',
				page,
			} );
		} );
	} );

	describe( 'startReordering', () => {
		it( 'should return the START_REORDERING action', () => {
			const result = startReordering();
			expect( result ).toStrictEqual( {
				type: 'START_REORDERING',
				order: undefined,
			} );
		} );
	} );

	describe( 'movePageToPosition', () => {
		it( 'should return the MOVE_PAGE action', () => {
			const page = 'foo';
			const index = 2;

			const result = movePageToPosition( page, index );
			expect( result ).toStrictEqual( {
				type: 'MOVE_PAGE',
				page,
				index,
			} );
		} );
	} );

	describe( 'saveOrder', () => {
		it( 'should return the STOP_REORDERING action', () => {
			const result = saveOrder();
			expect( result ).toStrictEqual( {
				type: 'STOP_REORDERING',
			} );
		} );
	} );

	describe( 'resetOrder', () => {
		it( 'should return the RESET_ORDER action', () => {
			const result = resetOrder();
			expect( result ).toStrictEqual( {
				type: 'RESET_ORDER',
				order: undefined,
			} );
		} );
	} );

	describe( 'setCopiedMarkup', () => {
		it( 'should return the SET_COPIED_MARKUP action', () => {
			const result = setCopiedMarkup( 'Hello' );

			expect( result ).toStrictEqual( {
				type: 'SET_COPIED_MARKUP',
				markup: 'Hello',
			} );
		} );
	} );

	describe( 'clearCopiedMarkup', () => {
		it( 'should return the CLEAR_COPIED_MARKUP action', () => {
			const result = clearCopiedMarkup();

			expect( result ).toStrictEqual( {
				type: 'CLEAR_COPIED_MARKUP',
			} );
		} );
	} );
} );
