import { createRoot } from '@wordpress/element';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import QuizPanel from './components/quiz-panel';

const PluginDocumentSettingPanelFilled = () => (
	<PluginDocumentSettingPanel
		name="premiaquiz"
		title={ __( 'Premia Quiz', 'premiaquiz' ) }
	>
		<QuizPanel />
	</PluginDocumentSettingPanel>
);

registerPlugin( 'premiaquiz-document-settings', {
	render: PluginDocumentSettingPanelFilled,
} );

const root = document.getElementById( 'premiaquiz-admin' );

if ( root ) {
	createRoot( root ).render( <PluginDocumentSettingPanelFilled /> );
}
