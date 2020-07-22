/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { Component } from '@wordpress/element';
import { ResizableBox } from '@wordpress/components';

/**
 * Internal dependencies
 */
import withSnapTargets from '../higher-order/with-snap-targets';
import './edit.css';
import { getPercentageFromPixels, getRelativeElementPosition } from '../../helpers';
import { getBestSnapLines } from '../../helpers/snapping';
import { BLOCK_RESIZING_SNAP_GAP } from '../../constants';
import {
	getResizedWidthAndHeight,
	getBlockTextElement,
	getPositionAfterResizing,
} from './helpers';

let lastSeenX = 0,
	lastSeenY = 0,
	lastWidth,
	lastHeight,
	blockElement = null,
	blockElementTop,
	blockElementLeft,
	imageWrapper,
	textBlockWrapper,
	textElement;

class EnhancedResizableBox extends Component {
	constructor( ...args ) {
		super( ...args );
		this.state = {
			isResizing: false,
		};
	}

	render() {
		const {
			angle,
			blockName,
			ampFitText,
			hasTextContent,
			minWidth,
			minHeight,
			onResizeStart,
			onResizeStop,
			children,
			...otherProps
		} = this.props;

		let {
			width,
			height,
		} = this.props;

		const { isResizing } = this.state;

		const isImage = 'core/image' === blockName;
		const isText = 'amp/amp-story-text' === blockName;

		// Ensure that these props are not passed down.
		const {
			clientId,
			snapGap,
			horizontalTargets,
			verticalTargets,
			setSnapLines,
			clearSnapLines,
			parentBlockElement,
			...childProps
		} = otherProps;

		return (
			<ResizableBox
				{ ...childProps }
				className={ classnames(
					'amp-story-resize-container',
					{
						'is-resizing': isResizing,
					},
				) }
				size={ {
					height,
					width,
				} }
				enable={ {
					top: true,
					right: true,
					bottom: true,
					left: true,
					topRight: true,
					bottomRight: true,
					bottomLeft: true,
					topLeft: true,
				} }
				onResizeStop={ ( event, direction ) => {
					const { deltaW, deltaH } = getResizedWidthAndHeight( event, angle, lastSeenX, lastSeenY, direction );
					let appliedWidth = width + deltaW;
					let appliedHeight = height + deltaH;

					// Restore the full height for Text block wrapper.
					if ( textBlockWrapper ) {
						textBlockWrapper.style.height = '100%';
					}

					// Ensure the measures not crossing limits.
					appliedWidth = appliedWidth < lastWidth ? lastWidth : appliedWidth;
					appliedHeight = appliedHeight < lastHeight ? lastHeight : appliedHeight;

					const elementTop = parseFloat( blockElement.style.top );
					const elementLeft = parseFloat( blockElement.style.left );

					const positionTop = Number( elementTop.toFixed( 2 ) );
					const positionLeft = Number( elementLeft.toFixed( 2 ) );

					this.setState( { isResizing: false } );

					clearSnapLines();

					onResizeStop( {
						width: parseInt( appliedWidth ),
						height: parseInt( appliedHeight ),
						positionTop,
						positionLeft,
					} );
				} }
				onResizeStart={ ( event, direction, element ) => {
					lastSeenX = event.clientX;
					lastSeenY = event.clientY;
					lastWidth = width;
					lastHeight = height;
					blockElement = element.closest( '.wp-block' ).parentNode;
					blockElementTop = blockElement.style.top;
					blockElementLeft = blockElement.style.left;
					if ( isImage ) {
						imageWrapper = blockElement.querySelector( 'figure .components-resizable-box__container' );
					}
					textElement = ! ampFitText ? getBlockTextElement( blockName, blockElement ) : null;

					if ( ampFitText && isText ) {
						textBlockWrapper = blockElement.querySelector( '.with-line-height' );
					} else {
						// If the textBlockWrapper was set previously, make sure it's line height is reset, too.
						if ( textBlockWrapper ) {
							textBlockWrapper.style.lineHeight = 'initial';
						}
						textBlockWrapper = null;
					}

					this.setState( { isResizing: true } );

					clearSnapLines();

					onResizeStart();
				} }
				onResize={ ( event, direction, element ) => { // eslint-disable-line complexity
					const { deltaW, deltaH } = getResizedWidthAndHeight( event, angle, lastSeenX, lastSeenY, direction );

					// Handle case where media is inserted from URL.
					if ( isImage && ! width && ! height ) {
						width = blockElement.clientWidth;
						height = blockElement.clientHeight;
					}

					// If the new width/height is below the minimum limit, set the minimum limit as the width/height instead.
					let appliedWidth = Math.max( minWidth, ( width + deltaW ) );
					let appliedHeight = Math.max( minHeight, ( height + deltaH ) );

					const isReducing = 0 > deltaW || 0 > deltaH;

					// Track if resizing has reached its minimum limits to fit the text inside.
					let reachedMinLimit = false;
					// The following calculation is needed only when content has been added to the Text block.
					if ( textElement && isReducing && hasTextContent ) {
						// If we have a rotated block, let's assign the width and height for measuring.
						// Without assigning the new measure, the calculation would be incorrect due to angle.
						if ( angle ) {
							textElement.style.width = appliedWidth + 'px';
							textElement.style.height = appliedHeight + 'px';
						}

						// Whenever reducing the size of a text element, set height to `auto`
						// (overwriting the above for angled text boxes) to get proper scroll height.
						if ( isText ) {
							textElement.style.height = 'auto';
						}

						// If the applied measures get too small for text, use the previous measures instead.
						const scrollWidth = textElement.scrollWidth;
						const scrollHeight = textElement.scrollHeight;
						// If the text goes over either of the edges, stop resizing from both sides
						// since the text is filling in the room from both sides at the same time.
						if ( appliedWidth < scrollWidth || appliedHeight < scrollHeight ) {
							reachedMinLimit = true;
							appliedWidth = lastWidth;
							appliedHeight = lastHeight;
						}
						// If we have rotated block, let's restore the correct measures.
						if ( angle ) {
							if ( ! isText ) {
								textElement.style.width = 'initial';
								textElement.style.height = '100%';
							} else if ( isText && ! ampFitText ) {
								textElement.style.width = '100%';
							}
						}

						// Reset text element height.
						if ( isText ) {
							textElement.style.height = '';
						}
					}

					// If limits were not reached yet, do the calculations for positioning.
					if ( ! reachedMinLimit ) {
						const updatedPos = getPositionAfterResizing( {
							direction,
							angle,
							isText,
							oldWidth: width,
							oldHeight: height,
							newWidth: appliedWidth,
							newHeight: appliedHeight,
							oldPositionLeft: blockElementLeft,
							oldPositionTop: blockElementTop,
						} );
						blockElement.style.left = getPercentageFromPixels( 'x', updatedPos.left ) + '%';
						blockElement.style.top = getPercentageFromPixels( 'y', updatedPos.top ) + '%';
					}

					element.style.width = appliedWidth + 'px';
					element.style.height = appliedHeight + 'px';

					// Get the correct dimensions in case the block is rotated, as rotation is only applied to the clone's inner element(s).
					// We calculate with the block's actual dimensions relative to the page it's on.
					const {
						top: actualTop,
						right: actualRight,
						bottom: actualBottom,
						left: actualLeft,
					} = getRelativeElementPosition( blockElement.querySelector( '.wp-block' ), parentBlockElement );

					const snappingEnabled = ! event.getModifierState( 'Alt' );

					if ( snappingEnabled ) {
						const [ horizontalEdgeSnaps, horizontalCenterSnaps ] = horizontalTargets( actualTop, actualBottom );
						const [ verticalEdgeSnaps, verticalCenterSnaps ] = verticalTargets( actualLeft, actualRight );
						setSnapLines( [
							...getBestSnapLines( horizontalEdgeSnaps, horizontalCenterSnaps, actualLeft, actualRight, BLOCK_RESIZING_SNAP_GAP ),
							...getBestSnapLines( verticalEdgeSnaps, verticalCenterSnaps, actualTop, actualBottom, BLOCK_RESIZING_SNAP_GAP ),
						] );
					} else {
						clearSnapLines();
					}

					lastWidth = appliedWidth;
					lastHeight = appliedHeight;

					if ( textBlockWrapper ) {
						if ( ampFitText ) {
							textBlockWrapper.style.lineHeight = appliedHeight + 'px';
						}
						// Also add the height to the wrapper since the background color is set to the wrapper.
						textBlockWrapper.style.height = appliedHeight + 'px';
					}

					// If it's image, let's change the width and height of the image, too.
					if ( imageWrapper && isImage ) {
						imageWrapper.style.width = appliedWidth + 'px';
						imageWrapper.style.height = appliedHeight + 'px';
					}
				} }
			>
				{ children }
			</ResizableBox>
		);
	}
}

EnhancedResizableBox.defaultProps = {
	snapGap: 0,
};

EnhancedResizableBox.propTypes = {
	ampFitText: PropTypes.bool,
	angle: PropTypes.number,
	blockName: PropTypes.string,
	hasTextContent: PropTypes.bool,
	clientId: PropTypes.string,
	minWidth: PropTypes.number,
	minHeight: PropTypes.number,
	onResizeStart: PropTypes.func.isRequired,
	onResizeStop: PropTypes.func.isRequired,
	children: PropTypes.node.isRequired,
	width: PropTypes.number,
	height: PropTypes.number,
	horizontalTargets: PropTypes.func.isRequired,
	verticalTargets: PropTypes.func.isRequired,
	snapGap: PropTypes.number.isRequired,
	setSnapLines: PropTypes.func.isRequired,
	clearSnapLines: PropTypes.func.isRequired,
	parentBlockElement: PropTypes.object,
};

export default withSnapTargets( EnhancedResizableBox );
