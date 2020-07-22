/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { ReactElement } from 'react';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	PanelColorSettings,
} from '@wordpress/block-editor';
import {
	Button,
	RangeControl,
} from '@wordpress/components';

/**
 * Displays the page background color settings.
 *
 * @param {Object} props Component props.
 * @param {Array} props.backgroundColors Current background colors.
 * @param {Function} props.setAttributes setAttributes callback.
 * @param {number} props.overlayOpacity Overlay opacity.
 * @param {number} props.hasMedia Whether the page has a media item set.
 * @return {ReactElement} Component.
 */
const BackgroundColorSettings = ( { backgroundColors, setAttributes, overlayOpacity, hasMedia } ) => {
	const hasColors = backgroundColors.some( ( color ) => color && Object.values( color ).some( Boolean ) );

	const removeBackgroundColor = ( index ) => {
		backgroundColors.splice( index, 1 );
		setAttributes( { backgroundColors: JSON.stringify( backgroundColors ) } );
	};

	const setBackgroundColors = ( value, index ) => {
		backgroundColors[ index ] = {
			color: value,
		};

		setAttributes( {
			backgroundColors: JSON.stringify( backgroundColors ),
			overlayOpacity: ! hasColors && overlayOpacity === 100 && hasMedia ? 50 : overlayOpacity,
		} );
	};

	const getOverlayColorSettings = () => {
		if ( ! backgroundColors.length ) {
			return [
				{
					value: undefined,
					onChange: ( value ) => {
						setBackgroundColors( value, 0 );
					},
					label: __( 'Background Color', 'amp' ),
				},
			];
		}

		const backgroundColorSettings = [];
		const useNumberedLabels = backgroundColors.length > 1;

		backgroundColors.forEach( ( color, index ) => {
			backgroundColorSettings[ index ] = {
				value: color ? color.color : undefined,
				onChange: ( value ) => {
					setBackgroundColors( value, index );
				},
				/* translators: %s: color number */
				label: useNumberedLabels ? sprintf( __( 'Color %s', 'amp' ), index + 1 ) : __( 'Background Color', 'amp' ),
			};
		} );

		return backgroundColorSettings;
	};

	return (
		<PanelColorSettings
			title={ __( 'Color Settings', 'amp' ) }
			colorSettings={ getOverlayColorSettings() }
		>
			<p>
				{ backgroundColors.length < 2 &&
				<Button
					onClick={ () => setBackgroundColors( null, 1 ) }
					isSmall>
					{ __( 'Add Gradient', 'amp' ) }
				</Button>
				}
				{ backgroundColors.length > 1 &&
				<Button
					onClick={ () => removeBackgroundColor( backgroundColors.length - 1 ) }
					isLink
					isDestructive>
					{ __( 'Remove Gradient', 'amp' ) }
				</Button>
				}
			</p>
			{ hasColors &&
			<RangeControl
				label={ __( 'Opacity', 'amp' ) }
				value={ overlayOpacity }
				onChange={ ( value ) => setAttributes( { overlayOpacity: value } ) }
				min={ 0 }
				max={ 100 }
				step={ 5 }
				required
			/>
			}
		</PanelColorSettings>
	);
};

BackgroundColorSettings.propTypes = {
	backgroundColors: PropTypes.array,
	overlayOpacity: PropTypes.number,
	setAttributes: PropTypes.func.isRequired,
	hasMedia: PropTypes.bool.isRequired,
};

export default BackgroundColorSettings;
