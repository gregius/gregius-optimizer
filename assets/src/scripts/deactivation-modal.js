import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { useState, useEffect, createRoot } from '@wordpress/element';
import { Modal, RadioControl, Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const { deactivateId, removeData } = window.ggOptimizerDeactivation || {};

function DeactivationModal() {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ href, setHref ] = useState( '' );
	const [ choice, setChoice ] = useState( removeData ? 'remove' : 'keep' );

	useEffect( () => {
		const link = document.getElementById( deactivateId );
		if ( ! link ) {
			return;
		}
		const onClick = ( e ) => {
			e.preventDefault();
			setHref( link.href );
			setIsOpen( true );
		};
		link.addEventListener( 'click', onClick );
		return () => link.removeEventListener( 'click', onClick );
	}, [ deactivateId ] );

	const proceed = async () => {
		try {
			await apiFetch( {
				path: '/gg-optimizer/v1/data-retention',
				method: 'POST',
				data: { remove_data: choice === 'remove' },
			} );
		} catch ( e ) {
			// Deactivation proceeds regardless; data preserved by default.
		}
		window.location.href = href;
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'Deactivate Gregius Optimizer', 'gregius-optimizer' ) }
			onRequestClose={ proceed }
		>
			<p>
				{ __(
					'What should happen to Gregius Optimizer data if you later delete the plugin?',
					'gregius-optimizer'
				) }
			</p>
			<RadioControl
				label={ __( 'Data handling on plugin deletion', 'gregius-optimizer' ) }
				selected={ choice }
				options={ [
					{
						label: __( 'Keep my data (recommended)', 'gregius-optimizer' ),
						value: 'keep',
					},
					{
						label: __(
							'Remove all data when the plugin is deleted',
							'gregius-optimizer'
						),
						value: 'remove',
					},
				] }
				onChange={ setChoice }
				help={ __(
					'Deactivating never removes data — this only affects deletion.',
					'gregius-optimizer'
				) }
			/>
			<Button variant="primary" onClick={ proceed }>
				{ __( 'Deactivate', 'gregius-optimizer' ) }
			</Button>
		</Modal>
	);
}

domReady( () => {
	const rootEl = document.createElement( 'div' );
	document.body.appendChild( rootEl );
	createRoot( rootEl ).render( <DeactivationModal /> );
} );
