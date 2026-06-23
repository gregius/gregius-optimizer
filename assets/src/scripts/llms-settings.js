import apiFetch from "@wordpress/api-fetch";
import { PluginDocumentSettingPanel } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";
import { registerPlugin } from "@wordpress/plugins";
import { Fragment, useState, useEffect } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import {
  Button,
  ExternalLink,
  Modal,
  Notice,
  PanelRow,
  Spinner,
  TextareaControl,
  ToggleControl,
} from "@wordpress/components";

const META_INCLUDE_KEY = "_gg_optimizer_include_in_llms";
const META_DESC_KEY = "_gg_optimizer_llms_description";

const asEditedString = (value) => {
  if ("string" === typeof value) {
    return value;
  }
  if (value && "string" === typeof value.raw) {
    return value.raw;
  }
  return "";
};

const trimWords = (text, count) => {
  if (!text) {
    return "";
  }
  return text.split(/\s+/).slice(0, count).join(" ");
};

const stripBlocks = (html) => {
  if (!html) {
    return "";
  }
  return html
    .replace(/<!--[\s\S]*?-->/g, "")
    .replace(/<[^>]*>/g, "")
    .replace(/\s+/g, " ")
    .trim();
};

const LLMSSettings = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [context, setContext] = useState("");
  const [hasCustom, setHasCustom] = useState(false);
  const [preview, setPreview] = useState(null);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [featureEnabled, setFeatureEnabled] = useState( true );

  const currentPostId = useSelect(
    (select) => select("core/editor").getCurrentPostId(),
    [],
  );
  const currentPostMeta = useSelect(
    (select) => select("core/editor").getEditedPostAttribute("meta") || {},
    [],
  );
  const currentPostTitle = useSelect(
    (select) =>
      asEditedString(select("core/editor").getEditedPostAttribute("title")),
    [],
  );
  const currentPostExcerpt = useSelect(
    (select) =>
      asEditedString(select("core/editor").getEditedPostAttribute("excerpt")),
    [],
  );
  const currentPostContent = useSelect(
    (select) =>
      asEditedString(select("core/editor").getEditedPostAttribute("content")),
    [],
  );

  const { editPost } = useDispatch("core/editor");

  const getEffectiveDescription = () => {
    const customDesc = currentPostMeta[META_DESC_KEY];
    if (customDesc) return stripBlocks(customDesc);
    if (currentPostExcerpt) return currentPostExcerpt;
    if (currentPostContent) return trimWords(stripBlocks(currentPostContent), 20);
    return "";
  };

  const isToggleOn = !!currentPostMeta[META_INCLUDE_KEY];

  const openModal = async () => {
    setIsOpen(true);
    setError("");
    setSuccess("");
    setPreview(null);

    try {
      const data = await apiFetch({
        path: "/gg-optimizer/v1/llms-override",
        method: "GET",
      });
      const loadedContext = data.llms_context || "";
      setContext(loadedContext);
      setHasCustom(!!data.llms_context);
      await loadPreview(loadedContext);

      apiFetch( { path: '/gg-optimizer/v1/feature-toggles' } )
        .then( ( data ) => {
          if ( data && typeof data.llms === 'boolean' ) {
            setFeatureEnabled( data.llms );
          }
        } )
        .catch( () => {} );
    } catch (err) {
      setError(
        err?.message || __("Unable to load settings.", "gregius-optimizer"),
      );
    }
  };

  const closeModal = () => {
    setIsOpen(false);
    setPreview(null);
    setError("");
    setSuccess("");
  };

  const save = async () => {
    setIsSaving(true);
    setError("");
    setSuccess("");

    apiFetch( {
      path: '/gg-optimizer/v1/feature-toggles',
      method: 'POST',
      data: { toggles: { llms: featureEnabled } },
    } );

    try {
      await apiFetch({
        path: "/gg-optimizer/v1/llms-override",
        method: "POST",
        data: { llms_override: context },
      });
      setHasCustom(true);
      setSuccess(__("Settings updated.", "gregius-optimizer"));
    } catch (err) {
      setError(
        err?.message || __("Unable to save settings.", "gregius-optimizer"),
      );
    } finally {
      setIsSaving(false);
    }
  };

  const reset = async () => {
    setIsSaving(true);
    setError("");
    setSuccess("");
    setPreview(null);
    try {
      await apiFetch({
        path: "/gg-optimizer/v1/llms-override",
        method: "POST",
        data: { llms_override: "" },
      });
      const data = await apiFetch({
        path: "/gg-optimizer/v1/llms-override",
        method: "GET",
      });
      const loadedContext = data.llms_context || "";
      setContext(loadedContext);
      setHasCustom(false);
      setSuccess(__("Reset to defaults.", "gregius-optimizer"));
      await loadPreview(loadedContext);
    } catch (err) {
      setError(
        err?.message || __("Unable to reset settings.", "gregius-optimizer"),
      );
    } finally {
      setIsSaving(false);
    }
  };

  const loadPreview = async (ctx) => {
    const contextForPreview = ctx !== undefined ? ctx : context;

    setPreviewLoading(true);
    setError("");

    const unsavedToggles = currentPostId
      ? { [currentPostId]: isToggleOn }
      : {};

    const unsavedDescriptions = currentPostId && isToggleOn
      ? { [currentPostId]: currentPostMeta[META_DESC_KEY] || "" }
      : {};

    try {
      const data = await apiFetch({
        path: "/gg-optimizer/v1/llms-preview",
        method: "POST",
        data: {
          llms_override: contextForPreview,
          unsaved_toggles: unsavedToggles,
          unsaved_descriptions: unsavedDescriptions,
        },
      });
      setPreview(data.llms_txt || "");
    } catch (err) {
      setError(
        err?.message || __("Unable to load preview.", "gregius-optimizer"),
      );
    } finally {
      setPreviewLoading(false);
    }
  };

  const descDependency = currentPostMeta[META_DESC_KEY];

  useEffect(() => {
    if (!isOpen) {
      return;
    }
    const timer = setTimeout(loadPreview, 500);
    return () => clearTimeout(timer);
  }, [context, isToggleOn, descDependency]);

  return (
    <>
      <PluginDocumentSettingPanel
        name="gg-optimizer-llms-settings"
        title={__("LLMs", "gregius-optimizer")}
        className="gg-optimizer-llms-settings"
        initialOpen={false}
      >
        <PanelRow>
          <div>
            <p>
              {__("Edit the llms.txt context.", "gregius-optimizer")}{" "}
              <ExternalLink href="https://llmstxt.org/">
                {__("Learn more at llmstxt.org", "gregius-optimizer")}
              </ExternalLink>
            </p>
            <Button variant="secondary" onClick={openModal}>
              {__("Settings", "gregius-optimizer")}
            </Button>
          </div>
        </PanelRow>
      </PluginDocumentSettingPanel>

      {isOpen && (
        <Modal
          title={__("LLMs", "gregius-optimizer")}
          onRequestClose={closeModal}
        >
          <div
            style={{
              overflowY: "auto",
              paddingRight: "8px",
              maxWidth: "600px",
              display: "flex",
              flexDirection: "column",
              gap: "1.25rem",
            }}
          >
            <ToggleControl
              label={ __( 'Enable llms.txt', 'gregius-optimizer' ) }
              checked={ featureEnabled }
              onChange={ ( value ) => setFeatureEnabled( value ) }
              __nextHasNoMarginBottom
            />

            <div
              className={
                featureEnabled ? '' : 'gg-optimizer-feature-disabled'
              }
            >
              <div>
              <p>
                {__(
                  "A proposal to standardise on using an /llms.txt file to provide information to help LLMs use a website at inference time.",
                  "gregius-optimizer",
                )}{" "}
                <ExternalLink href="https://llmstxt.org/">
                  {__("Learn more at llmstxt.org", "gregius-optimizer")}
                </ExternalLink>
              </p>
            </div>

            <h2>{__("Settings", "gregius-optimizer")}</h2>

            <TextareaControl
              label={__("Global Context", "gregius-optimizer")}
              value={context}
              onChange={(value) => {
                setContext(value);
                setPreview(null);
              }}
              rows={15}
              __nextHasNoMarginBottom
            />

            <div
              style={{
                border: "1px solid #ddd",
                borderRadius: "4px",
                padding: "1rem",
                display: "flex",
                flexDirection: "column",
                gap: "0.75rem",
              }}
            >
              <ToggleControl
                label={__(
                  "Include the current document in site's llms.txt",
                  "gregius-optimizer",
                )}
                checked={isToggleOn}
                onChange={(value) => {
                  editPost({
                    meta: {
                      ...currentPostMeta,
                      [META_INCLUDE_KEY]: !!value,
                    },
                  });
                }}
                help={
                  <Fragment>
                    {__(
                      "Include this document in your site's llms.txt to improve understanding and relevant retrieval.",
                      "gregius-optimizer",
                    )}{" "}
                    <ExternalLink href="https://llmstxt.org/">
                      {__("Learn more at llmstxt.org", "gregius-optimizer")}
                    </ExternalLink>
                  </Fragment>
                }
              />

              {isToggleOn && (
                <TextareaControl
                  label={__(
                    "Description",
                    "gregius-optimizer",
                  )}
                  help={__(
                    "Leave empty to auto-generate from post excerpt or content.",
                    "gregius-optimizer",
                  )}
                  value={currentPostMeta[META_DESC_KEY] || ""}
                  onChange={(value) => {
                    editPost({
                      meta: {
                        ...currentPostMeta,
                        [META_DESC_KEY]: value,
                      },
                    });
                  }}
                  rows={3}
                  placeholder={getEffectiveDescription()}
                  __nextHasNoMarginBottom
                />
              )}
            </div>

            </div>

            <div
              style={{
                display: "flex",
                gap: "0.5rem",
                alignItems: "center",
              }}
            >
              <Button variant="primary" onClick={save} disabled={isSaving}>
                {isSaving
                  ? __("Updating\u2026", "gregius-optimizer")
                  : __("Update", "gregius-optimizer")}
              </Button>
              <Button
                variant="secondary"
                onClick={reset}
                disabled={isSaving || !hasCustom}
              >
                {__("Reset to defaults", "gregius-optimizer")}
              </Button>
            </div>

            {error && <Notice status="error" isDismissible onRemove={() => setError("")}>{error}</Notice>}
            {success && <Notice status="success" isDismissible onRemove={() => setSuccess("")}>{success}</Notice>}

            {previewLoading && <Spinner />}

            {!previewLoading && preview && featureEnabled && (
              <div>
                <h2>{__("Preview", "gregius-optimizer")}</h2>
                <pre
                  style={{
                    border: "1px solid #ddd",
                    borderRadius: "4px",
                    padding: "1rem",
                    whiteSpace: "pre-wrap",
                    wordBreak: "break-word",
                    fontSize: "13px",
                    lineHeight: 1.5,
                    overflow: "auto",
                    background: "#f8f8f8",
                  }}
                >
                  {preview}
                </pre>
              </div>
            )}
          </div>
        </Modal>
      )}
    </>
  );
};

registerPlugin("gg-optimizer-llms-settings", { render: LLMSSettings });
