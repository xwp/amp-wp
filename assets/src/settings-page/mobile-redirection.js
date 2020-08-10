
/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RedirectToggle } from '../components/redirect-toggle';
import { Options } from '../components/options-context-provider';

/**
 * Mobile redirection section of the settings page.
 */
export function MobileRedirection() {
	const { editedOptions } = useContext( Options );

	const { theme_support: themeSupport } = editedOptions || {};

	// Don't show if the mode is standard or the themeSupport is not yet set.
	if ( ! themeSupport || 'standard' === themeSupport ) {
		return null;
	}

	return (
		<section className="mobile-redirection">
			<RedirectToggle direction="left" />
		</section>
	);
}
