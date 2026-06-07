import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ExternalLink, PanelBody, ToggleControl } from '@wordpress/components';

const SUPPORTED_BLOCK = 'core/site-logo';
const ATTR_NAME = 'organizationLogoSchema';

const addAttributes = ( settings, name ) => {
	if ( name !== SUPPORTED_BLOCK ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			[ ATTR_NAME ]: {
				type: 'boolean',
				default: false,
			},
		},
	};
};

addFilter(
	'blocks.registerBlockType',
	'gregius-optimizer/logo-schema/attributes',
	addAttributes
);

const withInspectorControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( props.name !== SUPPORTED_BLOCK ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes, isSelected } = props;

		return (
			<Fragment>
				<BlockEdit { ...props } />
				{ isSelected && (
					<InspectorControls>
						<PanelBody
							title={ __( 'Organization Logo', 'gregius-optimizer' ) }
							initialOpen={ true }
						>
							<ToggleControl
							label={ __( "Include logo in site's structured data", 'gregius-optimizer' ) }
							help={
								<Fragment>
									{ __( "Include the site logo in the site's <head> as structured data (Organization.logo). This may help search engines better understand your organization.", 'gregius-optimizer' ) }{ ' ' }
									<ExternalLink href="https://schema.org/logo">
										{ __( 'Learn more at schema.org', 'gregius-optimizer' ) }
									</ExternalLink>
								</Fragment>
								}
								checked={ !! attributes[ ATTR_NAME ] }
								onChange={ ( value ) =>
									setAttributes( { [ ATTR_NAME ]: !! value } )
								}
							/>
						</PanelBody>
					</InspectorControls>
				) }
			</Fragment>
		);
	};
}, 'withOrganizationLogoSchemaInspectorControls' );

addFilter(
	'editor.BlockEdit',
	'gregius-optimizer/logo-schema/inspector',
	withInspectorControls
);
