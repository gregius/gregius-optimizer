import { addFilter } from "@wordpress/hooks";
import { createHigherOrderComponent } from "@wordpress/compose";
import { Fragment } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { InspectorControls } from "@wordpress/block-editor";
import { ExternalLink, PanelBody, ToggleControl } from "@wordpress/components";

const SUPPORTED_BLOCK = "core/social-links";
const ATTR_NAME = "sameAsSchema";

const addAttributes = (settings, name) => {
  if (name !== SUPPORTED_BLOCK) {
    return settings;
  }

  return {
    ...settings,
    attributes: {
      ...settings.attributes,
      [ATTR_NAME]: {
        type: "boolean",
        default: false,
      },
    },
  };
};

addFilter(
  "blocks.registerBlockType",
  "gregius-optimizer/sameas-schema/attributes",
  addAttributes,
);

const withInspectorControls = createHigherOrderComponent((BlockEdit) => {
  return (props) => {
    if (props.name !== SUPPORTED_BLOCK) {
      return <BlockEdit {...props} />;
    }

    const { attributes, setAttributes, isSelected } = props;

    return (
      <Fragment>
        <BlockEdit {...props} />
        {isSelected && (
          <InspectorControls>
            <PanelBody
              title={__("Organization SameAs", "gregius-optimizer")}
              initialOpen={true}
            >
              <ToggleControl
                label={__("Include in site's schema", "gregius-optimizer")}
                help={
                  <Fragment>
                    {__(
                      "Include social links in the site's <head> as structured data (Organization.sameAs). This may help search engines better understand your organization.",
                      "gregius-optimizer",
                    )}{" "}
                    <ExternalLink href="https://schema.org/sameAs">
                      {__("Learn more at schema.org", "gregius-optimizer")}
                    </ExternalLink>
                  </Fragment>
                }
                checked={!!attributes[ATTR_NAME]}
                onChange={(value) => setAttributes({ [ATTR_NAME]: !!value })}
              />
            </PanelBody>
          </InspectorControls>
        )}
      </Fragment>
    );
  };
}, "withSameAsSchemaInspectorControls");

addFilter(
  "editor.BlockEdit",
  "gregius-optimizer/sameas-schema/inspector",
  withInspectorControls,
);
