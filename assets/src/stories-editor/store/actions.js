/**
 * Returns an action object in signalling that a given item is now animated.
 *
 * @param {string}  page        ID of the page the item is on.
 * @param {string}  item        ID of the animated item.
 * @param {?string} predecessor Optional. ID of the item's predecessor in the animation order.
 *
 * @return {Object} Action object.
 */
export function addAnimation( page, item, predecessor ) {
	return {
		type: 'ADD_ANIMATION',
		page,
		item,
		predecessor,
	};
}

/**
 * Returns an action object in signalling that an item's animation type has changed.
 *
 * @param {string} page          ID of the page the item is on.
 * @param {string} item          ID of the animated item.
 * @param {string} animationType Animation type value.
 *
 * @return {Object} Action object.
 */
export function changeAnimationType( page, item, animationType ) {
	return {
		type: 'CHANGE_ANIMATION_TYPE',
		page,
		item,
		animationType,
	};
}

/**
 * Returns an action object in signalling that an item's animation duration has changed.
 *
 * @param {string} page     ID of the page the item is on.
 * @param {string} item     ID of the animated item.
 * @param {number} duration Animation delay value
 *
 * @return {Object} Action object.
 */
export function changeAnimationDuration( page, item, duration ) {
	return {
		type: 'CHANGE_ANIMATION_DURATION',
		page,
		item,
		duration,
	};
}

/**
 * Returns an action object in signalling that an item's animation delay has changed.
 *
 * @param {string} page  ID of the page the item is on.
 * @param {string} item  ID of the animated item.
 * @param {number} delay Animation delay value
 *
 * @return {Object} Action object.
 */
export function changeAnimationDelay( page, item, delay ) {
	return {
		type: 'CHANGE_ANIMATION_DELAY',
		page,
		item,
		delay,
	};
}

/**
 * Returns an action object in signalling that animation has been started.
 *
 * @param {string} page ID of the page the item is on.
 * @param {string} [item] Optional. ID of the animated item. If not passed, all items on the given page are animated.
 *
 * @return {Object} Action object.
 */
export function playAnimation( page, item ) {
	return {
		type: 'PLAY_ANIMATION',
		page,
		item,
	};
}

/**
 * Returns an action object in signalling that an animation has finished.
 *
 * @param {string} page ID of the page the item is on.
 * @param {string} item ID of the animated item.
 *
 * @return {Object} Action object.
 */
export function finishAnimation( page, item ) {
	return {
		type: 'FINISH_ANIMATION',
		page,
		item,
	};
}

/**
 * Returns an action object in signalling that an animation has been stopped.
 *
 * @param {string} page ID of the page the item is on.
 * @param {string} [item] Optional. ID of the animated item. If not passed, all items on the given page are stopped.
 *
 * @return {Object} Action object.
 */
export function stopAnimation( page, item ) {
	return {
		type: 'STOP_ANIMATION',
		page,
		item,
	};
}

/**
 * Returns an action object in signalling that the currently selected page has changed.
 *
 * Only a single page can be edited at a time.
 *
 * @param {string} page ID of the selected page.
 *
 * @return {Object} Action object.
 */
export function setCurrentPage( page ) {
	return {
		type: 'SET_CURRENT_PAGE',
		page,
	};
}

/**
 * Returns an action object in signalling that reorder mode should be initiated.
 *
 * @param {string[]} order The current block order.
 *
 * @return {Object} Action object.
 */
export function startReordering( order ) {
	return {
		type: 'START_REORDERING',
		order,
	};
}

/**
 * Returns an action object in signalling that a page should be moved within the collection.
 *
 * @param {string} page ID of the moved page.
 * @param {number} index New index.
 *
 * @return {Object} Action object.
 */
export function movePageToPosition( page, index ) {
	return {
		type: 'MOVE_PAGE',
		page,
		index,
	};
}

/**
 * Returns an action object in signalling that the changed page order should be saved.
 *
 * @return {Object} Action object.
 */
export function saveOrder() {
	return {
		type: 'STOP_REORDERING',
	};
}

/**
 * Returns an action object in signalling that the customized order should be reverted.
 *
 * @param {string[]} order The original block order.
 *
 * @return {Object} Action object.
 */
export function resetOrder( order ) {
	return {
		type: 'RESET_ORDER',
		order,
	};
}

/**
 * Returns an action object for setting copied block markup.
 *
 * @param {string} markup Markup copied to clipboard.
 * @return {Object} Action object.
 */
export function setCopiedMarkup( markup ) {
	return {
		type: 'SET_COPIED_MARKUP',
		markup,
	};
}

/**
 * Returns an action object signalling that copied markup needs to be cleared.
 *
 * @return {Object} Action object.
 */
export function clearCopiedMarkup() {
	return {
		type: 'CLEAR_COPIED_MARKUP',
	};
}
