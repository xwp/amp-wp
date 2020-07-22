/**
 * Internal dependencies
 */
import getBestSnapLines from '../getBestSnapLines';

describe( 'getBestSnapLines', () => {
	const edgeLines = {
		0: Array( 1 ),
		300: Array( 2 ),
		305: Array( 3 ),
	};
	const centerLines = {
		100: Array( 4 ),
	};

	const gap = 10;

	it( 'should return nothing if none match', () => {
		const start = 40;
		const end = 60;

		expect( getBestSnapLines( edgeLines, centerLines, start, end, gap ) ).toHaveLength( 0 );
	} );

	it( 'should return match if start matches within gap', () => {
		const start = 5;
		const end = 60;

		expect( getBestSnapLines( edgeLines, centerLines, start, end, gap ) ).toHaveLength( 1 );
	} );

	it( 'should return match if end matches within gap', () => {
		const start = 40;
		const end = 291;

		expect( getBestSnapLines( edgeLines, centerLines, start, end, gap ) ).toHaveLength( 2 );
	} );

	it( 'should return match if center matches within gap', () => {
		const start = 60;
		const end = 135;

		expect( getBestSnapLines( edgeLines, centerLines, start, end, gap ) ).toHaveLength( 4 );
	} );

	it( 'should return best if multiple edges match within gap', () => {
		const start = 5;
		const end = 190;

		// Center coordinate will match 100 better than start coordinate will match 0.
		expect( getBestSnapLines( edgeLines, centerLines, start, end, gap ) ).toHaveLength( 4 );
	} );

	it( 'should return best if multiple lines match the same edge within gap', () => {
		const start = 200;
		const end = 302;

		// End will match 300 better than 305, and no other edge will match.
		expect( getBestSnapLines( edgeLines, centerLines, start, end, gap ) ).toHaveLength( 2 );
	} );
} );
