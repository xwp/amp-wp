/**
 * AMP Latest Stories edit component, mainly forked from the Gutenberg 'Latest Posts' class LatestPostsEdit.
 */

/**
 * External dependencies
 */
import { isUndefined, pickBy } from 'lodash';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { Component } from '@wordpress/element';
import {
	PanelBody,
	Placeholder,
	QueryControls,
	Spinner,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { withSelect } from '@wordpress/data';

const { ampLatestStoriesBlockData } = window;

const blockName = 'amp/amp-latest-stories';

class LatestStoriesEdit extends Component {
	/**
	 * If the stylesheet isn't present, this adds it to the <head>.
	 *
	 * This block uses <ServerSideRender>, so sometimes the stylesheet isn't present.
	 * This checks if it is, and conditionally adds it.
	 */
	componentDidMount() {
		const stylesheetQuery = document.querySelector( `link[href="${ ampLatestStoriesBlockData.storyCardStyleURL }"]` );

		if ( ! stylesheetQuery ) {
			const stylesheet = document.createElement( 'link' );
			stylesheet.setAttribute( 'rel', 'stylesheet' );
			stylesheet.setAttribute( 'type', 'text/css' );
			stylesheet.setAttribute( 'href', ampLatestStoriesBlockData.storyCardStyleURL );
			document.head.appendChild( stylesheet );
		}
	}

	render() {
		const { attributes, setAttributes, latestStories } = this.props;
		const { order, orderBy, storiesToShow } = attributes;

		const isLoading = ! Array.isArray( latestStories );
		const storiesWithFeaturedImages = ( latestStories || [] ).filter( ( { featured_media: image } ) => image > 0 );
		const hasStories = storiesWithFeaturedImages.length > 0;

		const serverSideAttributes = {
			...attributes,
			useCarousel: false,
		};

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Latest Stories Settings', 'amp' ) }>
						<QueryControls
							{ ...{ order, orderBy } }
							numberOfItems={ storiesToShow }
							onOrderChange={ ( value ) => setAttributes( { order: value } ) }
							onOrderByChange={ ( value ) => setAttributes( { orderBy: value } ) }
							onNumberOfItemsChange={ ( value ) => setAttributes( { storiesToShow: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				{ ( isLoading || ! hasStories ) && (
					<Placeholder
						icon="admin-post"
						label={ __( 'Latest Stories', 'amp' ) }
					>
						{ isLoading ?
							<Spinner /> :
							__( 'No stories found.', 'amp' )
						}
					</Placeholder>
				) }
				{ hasStories && (
					<ServerSideRender
						block={ blockName }
						attributes={ serverSideAttributes }
					/>
				) }
			</>
		);
	}
}

LatestStoriesEdit.propTypes = {
	attributes: PropTypes.shape( {
		order: PropTypes.string,
		orderBy: PropTypes.string,
		storiesToShow: PropTypes.number,
	} ),
	setAttributes: PropTypes.func.isRequired,
	latestStories: PropTypes.array,
};

export default withSelect( ( select, props ) => {
	const { storiesToShow, order, orderBy } = props.attributes;
	const { getEntityRecords } = select( 'core' );
	const latestStoriesQuery = pickBy( {
		order,
		orderby: orderBy,
		per_page: storiesToShow,
	}, ( value ) => ! isUndefined( value ) );

	return {
		latestStories: getEntityRecords( 'postType', 'amp_story', latestStoriesQuery ),
	};
} )( LatestStoriesEdit );
