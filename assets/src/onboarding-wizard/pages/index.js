/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TechnicalBackground } from './technical-background';
import { TemplateMode } from './template-mode';
import { ChooseReaderTheme } from './choose-reader-theme';
import { Summary } from './summary';
import { Save } from './save';
import { Welcome } from './welcome';

/**
 * Settings for the pages in the application.
 */
export const PAGES = [
	{
		slug: 'welcome',
		title: __( 'Welcome', 'amp' ),
		PageComponent: Welcome,
		showTitle: false,
	},
	{
		slug: 'technical-background',
		title: __( 'Technical Background', 'amp' ),
		PageComponent: TechnicalBackground,
		showTitle: false,
	},
	{
		slug: 'template-modes',
		title: __( 'Template Modes', 'amp' ),
		PageComponent: TemplateMode,
	},
	{
		slug: 'theme-selection',
		title: __( 'Theme Selection', 'amp' ),
		PageComponent: ChooseReaderTheme,
	},
	{
		slug: 'summary',
		title: __( 'Review', 'amp' ),
		PageComponent: Summary,
	},
	{
		slug: 'done',
		title: __( 'Done', 'amp' ),
		PageComponent: Save,
		showTitle: false,
	},
];

