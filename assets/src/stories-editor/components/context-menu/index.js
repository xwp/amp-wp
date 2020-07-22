/**
 * External dependencies
 */
import { castArray } from 'lodash';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { cloneBlock, pasteHandler, serialize } from '@wordpress/blocks';
import { useEffect, useState, useRef } from '@wordpress/element';
import {
	MenuGroup,
	MenuItem,
	NavigableMenu,
	Popover,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './edit.css';
import {
	copyTextToClipBoard,
	isPageBlock,
	useIsBlockAllowedOnPage,
	useMoveBlockToPage,
	useDisplayPasteError,
} from '../../helpers';
import { ALLOWED_MOVABLE_BLOCKS, DISABLE_DUPLICATE_BLOCKS } from '../../constants';
import useOutsideClickChecker from './outside-click-checker';

const POPOVER_PROPS = {
	className: 'amp-story-right-click-menu__popover block-editor-block-settings-menu__popover editor-block-settings-menu__popover',
};

const ContextMenu = ( props ) => {
	const {
		clientIds,
		clientX,
		clientY,
		insidePercentageX,
		insidePercentageY,
	} = props;
	const [ isOpen, setIsOpen ] = useState( true );

	const {
		getBlock,
		getBlockOrder,
		getBlockRootClientId,
		getSettings,
	} = useSelect( ( select ) => select( 'core/block-editor' ), [] );

	const {
		getCopiedMarkup,
		getCurrentPage,
	} = useSelect( ( select ) => select( 'amp/story' ), [] );

	const {
		removeBlock,
		insertBlock,
		updateBlockAttributes,
	} = useDispatch( 'core/block-editor' );

	const { setCopiedMarkup } = useDispatch( 'amp/story' );

	const { __experimentalCanUserUseUnfilteredHTML: canUserUseUnfilteredHTML, isRTL } = getSettings();

	const isBlockAllowedOnPage = useIsBlockAllowedOnPage();
	const displayPasteError = useDisplayPasteError();

	const copyBlock = ( clientId ) => {
		const block = getBlock( clientId );
		const serialized = serialize( block );

		// Set the copied block to component state for being able to Paste.
		setCopiedMarkup( serialized );
		copyTextToClipBoard( serialized );
	};

	const getPageClientId = ( clientId ) => {
		let pageClientId;
		if ( isPageBlock( clientId ) ) {
			const clickedBlock = getBlock( clientId );
			pageClientId = clickedBlock.clientId;
		} else {
			pageClientId = getBlockRootClientId( clientId );
		}
		return pageClientId;
	};

	const processTextToPaste = ( text, clientId ) => {
		const mode = 'BLOCKS';
		const content = pasteHandler( {
			HTML: '',
			plainText: text,
			mode,
			tagName: null,
			canUserUseUnfilteredHTML,
		} );

		const pageClientId = getPageClientId( clientId );

		if ( ! pageClientId || ! content.length ) {
			return;
		}

		const blocksOnPage = getBlockOrder( pageClientId );
		content.forEach( ( pastedBlock ) => {
			if ( isBlockAllowedOnPage( pastedBlock.name, pageClientId ) ) {
				insertBlock( pastedBlock, blocksOnPage.length, pageClientId )
					.then( ( { blocks } ) => {
						blocks.forEach( ( block ) => {
							if ( ALLOWED_MOVABLE_BLOCKS.includes( block.name ) ) {
								updateBlockAttributes( block.clientId, {
									positionTop: insidePercentageY,
									positionLeft: insidePercentageX,
								} );
							}
						} );
					} )
					.catch( () => {
						displayPasteError( pastedBlock.name );
					} );
			} else {
				displayPasteError( pastedBlock.name );
			}
		} );
	};

	const cutBlock = ( clientId ) => {
		// First copy block and then remove it.
		copyBlock( clientId );
		removeBlock( clientId );
	};

	const pasteBlock = ( clientId ) => {
		const { navigator } = window;

		if ( navigator.clipboard && navigator.clipboard.readText ) {
			// We have to ask permissions for being able to read from clipboard.
			navigator.clipboard.readText().
				then( ( clipBoardText ) => {
				// If got permission, paste from clipboard.
					processTextToPaste( clipBoardText, clientId, insidePercentageY, insidePercentageX );
				} ).catch( () => {
				// If forbidden, use the markup from state instead.
					const text = getCopiedMarkup();
					processTextToPaste( text, clientId, insidePercentageY, insidePercentageX );
				} );
		} else {
			const text = getCopiedMarkup();
			processTextToPaste( text, clientId, insidePercentageY, insidePercentageX );
		}
	};
	const duplicateBlock = ( clientId ) => {
		const block = getBlock( clientId );
		if ( DISABLE_DUPLICATE_BLOCKS.includes( block.name ) ) {
			return;
		}

		let parentBlock;
		let index;

		if ( isPageBlock( clientId ) ) {
			parentBlock = '';
			const currentPageIndex = pageList.indexOf( clientId );
			index = currentPageIndex + 1;
		} else {
			parentBlock = getBlockRootClientId( clientId );
			index = null;
		}

		const clonedBlock = cloneBlock( block );
		insertBlock( clonedBlock, index, parentBlock );
	};

	useEffect( () => {
		setIsOpen( true );
	}, [ clientIds, clientX, clientY ] );

	const blockClientIds = castArray( clientIds );
	const firstBlockClientId = blockClientIds[ 0 ];
	const block = getBlock( firstBlockClientId );

	const onClose = () => {
		setIsOpen( false );
	};

	const containerRef = useRef( null );
	const { moveBlockToPage, getPageByOffset } = useMoveBlockToPage( firstBlockClientId );

	useOutsideClickChecker( containerRef, onClose );

	const position = {
		top: clientY,
		left: clientX,
	};

	const popoverPosition = ( isRTL ) ? 'bottom right' : 'bottom left';

	let blockActions = [];
	const pageList = getBlockOrder();
	const numPages = pageList.length;
	const hasMultiplePages = ( numPages > 1 );
	const isPage = isPageBlock( firstBlockClientId );

	// Don't allow any copying  or cutting of Page blocks.
	if ( ! isPage ) {
		blockActions = [
			{
				name: __( 'Copy Block', 'amp' ),
				blockAction: copyBlock,
				params: [ firstBlockClientId ],
				icon: 'admin-page',
				className: 'right-click-copy',
			},
			{
				name: __( 'Cut Block', 'amp' ),
				blockAction: cutBlock,
				params: [ firstBlockClientId ],
				icon: 'clipboard',
				className: 'right-click-cut',
			},

		];
	}

	// Disable Duplicate Block option for cta and attachment blocks.
	if ( block && ! DISABLE_DUPLICATE_BLOCKS.includes( block.name ) ) {
		if ( ! isPage ) {
			blockActions.push(
				{
					name: __( 'Duplicate Block', 'amp' ),
					blockAction: duplicateBlock,
					params: [ firstBlockClientId ],
					icon: 'admin-page',
					className: 'right-click-duplicate',
				},
			);
		} else {
			blockActions.push(
				{
					name: __( 'Duplicate Page', 'amp' ),
					blockAction: duplicateBlock,
					params: [ firstBlockClientId ],
					icon: 'admin-page',
					className: 'right-click-duplicate-page',
				},
			);
		}
	}

	// If more than one page, add options to move blocks between pages.
	if ( block && ! isPage && hasMultiplePages ) {
		const currentPage = getCurrentPage();
		const currentPagePosition = pageList.indexOf( currentPage );
		if ( currentPagePosition > 0 ) {
			const prevPage = getPageByOffset( -1 );
			if ( isBlockAllowedOnPage( block.name, prevPage ) ) {
				blockActions.push(
					{
						name: __( 'Send block to previous page', 'amp' ),
						blockAction: moveBlockToPage,
						params: [ prevPage ],
						icon: isRTL ? 'arrow-right-alt' : 'arrow-left-alt',
						className: 'right-click-previous-page',
					},
				);
			}
		}
		if ( currentPagePosition < ( numPages - 1 ) ) {
			const nextPage = getPageByOffset( 1 );
			if ( isBlockAllowedOnPage( block.name, nextPage ) ) {
				blockActions.push(
					{
						name: __( 'Send block to next page', 'amp' ),
						blockAction: moveBlockToPage,
						params: [ nextPage ],
						icon: isRTL ? 'arrow-left-alt' : 'arrow-right-alt',
						className: 'right-click-next-page',
					},
				);
			}
		}
	}

	// If not page, show "Remove block" option, otherwise if multiple pages, show "Remove page" option.
	if ( ! isPage ) {
		blockActions.push(
			{
				name: __( 'Remove Block', 'amp' ),
				blockAction: removeBlock,
				params: [ firstBlockClientId ],
				icon: 'trash',
				className: 'right-click-remove',
			},
		);
	} else if ( hasMultiplePages ) {
		blockActions.push(
			{
				name: __( 'Remove Page', 'amp' ),
				blockAction: removeBlock,
				params: [ firstBlockClientId ],
				icon: 'trash',
				className: 'right-click-remove-page',
			},
		);
	}

	// If value set in clipboard, show paste option.
	if ( getCopiedMarkup().length ) {
		blockActions.push(
			{
				name: __( 'Paste', 'amp' ),
				blockAction: pasteBlock,
				params: [ firstBlockClientId, insidePercentageY, insidePercentageX ],
				icon: 'pressthis',
				className: 'right-click-paste',
			},
		);
	}

	return (
		<div ref={ containerRef } className="amp-right-click-menu__container" style={ position }>
			{ isOpen && (
				<Popover
					className={ POPOVER_PROPS.className }
					position={ popoverPosition }
					onClose={ onClose }
					focusOnMount="firstElement"
				>
					<NavigableMenu
						role="menu"
					>
						{ blockActions.map( ( action ) => (
							<MenuGroup key={ `action-${ action.name }` } >
								<MenuItem
									className={ classnames( action.className, 'editor-block-settings-menu__control block-editor-block-settings-menu__control' ) }
									onClick={ () => {
										onClose();
										action.blockAction( ...action.params );
									} }
									icon={ action.icon }
								>
									{ action.name }
								</MenuItem>
							</MenuGroup>
						) ) }
					</NavigableMenu>
				</Popover>
			) }
		</div>
	);
};

ContextMenu.propTypes = {
	clientIds: PropTypes.arrayOf( PropTypes.string ).isRequired,
	clientX: PropTypes.number.isRequired,
	clientY: PropTypes.number.isRequired,
	insidePercentageX: PropTypes.number,
	insidePercentageY: PropTypes.number,
};

export default ContextMenu;
