import { PluginDocumentSettingPanel } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";
import { registerPlugin } from "@wordpress/plugins";
import { useSelect, useDispatch } from "@wordpress/data";
import { useState, useEffect, useCallback } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import {
  Button,
  ExternalLink,
  Modal,
  Notice,
  PanelRow,
  SelectControl,
  SnackbarList,
  Spinner,
} from "@wordpress/components";

const CopyIcon = ({ fill = "#666" }) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    height="18px"
    viewBox="0 -960 960 960"
    width="18px"
    fill={fill}
  >
    <path d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z" />
  </svg>
);

const SchemaSettingsSidebar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [globalDefaults, setGlobalDefaults] = useState({});
  const [editedDefaults, setEditedDefaults] = useState({});
  const [typeMap, setTypeMap] = useState([]);
  const [postTypes, setPostTypes] = useState([]);
  const [orgSettings, setOrgSettings] = useState({});
  const [editedOrgSettings, setEditedOrgSettings] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [schemaPreview, setSchemaPreview] = useState(null);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [copied, setCopied] = useState(false);
  const postId = useSelect(
    (select) => select("core/editor").getCurrentPostId(),
    [],
  );

  const postType = useSelect(
    (select) => select("core/editor").getCurrentPostType(),
    [],
  );
  const postTypeObject = useSelect(
    (select) => {
      if (!postType) return null;
      return select("core").getPostType(postType);
    },
    [postType],
  );
  const supportsCustomFields =
    postTypeObject &&
    postTypeObject.supports &&
    postTypeObject.supports["custom-fields"];
  const meta = useSelect(
    (select) => select("core/editor").getEditedPostAttribute("meta") || {},
    [],
  );
  const { editPost } = useDispatch("core/editor");

  const getSubtypesForType = useCallback(
    (typeKey) => {
      const found = typeMap.find((t) => t.key === typeKey);
      return found ? found.subtypes : [];
    },
    [typeMap],
  );

  const getTypeForSubtype = useCallback(
    (subtype) => {
      if (!subtype) return typeMap[0]?.key || "";
      const found = typeMap.find((t) => t.subtypes.includes(subtype));
      return found ? found.key : typeMap[0]?.key || "";
    },
    [typeMap],
  );

  useEffect(() => {
    if (!isOpen) return;
    setIsLoading(true);
    setError("");
    setSuccess("");

    Promise.all([
      apiFetch({ path: "/wp/v2/types" }),
      apiFetch({ path: "/gg-optimizer/v1/schema-global-settings" }),
    ])
      .then(([typesData, settingsData]) => {
        const NON_CONTENT_SLUGS = new Set([
          "attachment",
          "customize_changeset",
          "nav_menu_item",
          "wp_block",
          "wp_font_face",
          "wp_font_family",
          "wp_global_styles",
          "wp_navigation",
          "wp_template",
          "wp_template_part",
        ]);
        const types = Object.values(typesData)
          .filter((t) => !NON_CONTENT_SLUGS.has(t.slug))
          .sort((a, b) =>
            (a.label || a.name).localeCompare(b.label || b.name),
          );
        const defaults = settingsData.post_type_defaults || {};
        const orgSet = settingsData.schema_org_settings || {};
        setPostTypes(types);
        setGlobalDefaults(defaults);
        setEditedDefaults({ ...defaults });
        setOrgSettings(orgSet);
        setEditedOrgSettings({ ...orgSet });
        setTypeMap(settingsData.type_map || []);
      })
      .catch(() => {
        setError(
          __("Failed to load schema settings.", "gregius-optimizer"),
        );
      })
      .finally(() => {
        setIsLoading(false);
      });

    if (postId) {
      setPreviewLoading(true);
      apiFetch({ path: `/gg-optimizer/v1/schema-preview?post_id=${postId}` })
        .then((data) => setSchemaPreview(data))
        .catch(() => setSchemaPreview(null))
        .finally(() => setPreviewLoading(false));
    }
  }, [isOpen, postId]);

  const getDefaultForPostType = useCallback(
    (pt) => {
      if (editedDefaults[pt]) return editedDefaults[pt];
      return pt === "post"
        ? "BlogPosting"
        : pt === "page"
          ? "WebPage"
          : typeMap[0]?.subtypes[0] || "";
    },
    [editedDefaults, typeMap],
  );

  const handleDefaultChange = (postTypeSlug, subtype) => {
    setEditedDefaults((prev) => ({ ...prev, [postTypeSlug]: subtype }));
  };

  const currentSubtype = meta._gg_optimizer_schema_subtype || "";
  const resolvedDefaultSubtype = (() => {
    if (globalDefaults[postType]) return globalDefaults[postType];
    return postType === "post"
      ? "BlogPosting"
      : postType === "page"
        ? "WebPage"
        : typeMap[0]?.subtypes[0] || "";
})();

  const perPostSubtype = currentSubtype || resolvedDefaultSubtype;
  const perPostType = getTypeForSubtype(perPostSubtype);

  const handlePostSubtypeChange = (val) => {
    editPost({ meta: { ...meta, _gg_optimizer_schema_subtype: val } });
  };

  const handlePostTypeChange = (newType) => {
    const subtypes = getSubtypesForType(newType);
    handlePostSubtypeChange(subtypes[0]);
  };

  const hasChanges =
    JSON.stringify(editedDefaults) !== JSON.stringify(globalDefaults) ||
    JSON.stringify(editedOrgSettings) !== JSON.stringify(orgSettings);

  const handleUpdate = () => {
    if (!hasChanges) {
      setSuccess(__("Settings updated.", "gregius-optimizer"));
      return;
    }
    setIsSaving(true);
    apiFetch({
      path: "/gg-optimizer/v1/schema-global-settings",
      method: "POST",
      data: {
        post_type_defaults: editedDefaults,
        schema_org_settings: editedOrgSettings,
      },
    })
      .then(() => {
        setGlobalDefaults({ ...editedDefaults });
        setOrgSettings({ ...editedOrgSettings });
        setSuccess(__("Settings updated.", "gregius-optimizer"));
      })
      .catch(() => {
        setError(__("Failed to save settings.", "gregius-optimizer"));
      })
      .finally(() => {
        setIsSaving(false);
      });
  };

  const handleReset = () => {
    setIsSaving(true);
    setError("");
    setSuccess("");
    apiFetch({
      path: "/gg-optimizer/v1/schema-global-settings",
      method: "POST",
      data: {
        post_type_defaults: {},
        schema_org_settings: {},
      },
    })
      .then(() =>
        apiFetch({ path: "/gg-optimizer/v1/schema-global-settings" })
      )
      .then((settingsData) => {
        const defaults = settingsData.post_type_defaults || {};
        const orgSet = settingsData.schema_org_settings || {};
        setGlobalDefaults(defaults);
        setEditedDefaults({ ...defaults });
        setOrgSettings(orgSet);
        setEditedOrgSettings({ ...orgSet });
        setSuccess(__("Reset to defaults.", "gregius-optimizer"));
      })
      .catch(() => {
        setError(
          __("Failed to reset settings.", "gregius-optimizer"),
        );
      })
      .finally(() => {
        setIsSaving(false);
      });
  };

  const hasGlobalOverrides =
    Object.keys(globalDefaults).length > 0 ||
    Object.keys(orgSettings).length > 0;

  return (
    <>
      <PluginDocumentSettingPanel
        name="gg-optimizer-schema-sidebar"
        title={__("Schema", "gregius-optimizer")}
        className="gg-optimizer-schema-sidebar"
        initialOpen={false}
      >
        <PanelRow>
          <div>
            <p>
              {__(
                "Configure schema.org subtype defaults per post type and override individual posts.",
                "gregius-optimizer",
              )}{" "}
              <ExternalLink href="https://schema.org/">
                {__("Learn more about Schema.org", "gregius-optimizer")}
              </ExternalLink>
            </p>
            <Button variant="secondary" onClick={() => setIsOpen(true)}>
              {__("Settings", "gregius-optimizer")}
            </Button>
          </div>
        </PanelRow>
      </PluginDocumentSettingPanel>

      {isOpen && (
        <Modal
          title={__("Schema", "gregius-optimizer")}
          onRequestClose={() => setIsOpen(false)}
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
            {isLoading ? (
              <div style={{ textAlign: "center", padding: "2rem" }}>
                <Spinner />
              </div>
            ) : (
              <>
                <h2 style={{ margin: 0 }}>
                  {__("Global Settings", "gregius-optimizer")}
                </h2>

                <p style={{ fontSize: "13px", color: "#666", margin: 0 }}>
                  {__(
                    "Set the schema.org type for the site-wide Organization node.",
                    "gregius-optimizer",
                  )}
                </p>

                <div
                  style={{
                    display: "grid",
                    gridTemplateColumns: "1fr 1fr",
                    gap: "0.5rem",
                    alignItems: "center",
                    fontSize: "13px",
                  }}
                >
                  <span style={{ fontWeight: 600 }}>
                    {__("Organization Type", "gregius-optimizer")}
                  </span>
                  <SelectControl
                    value={editedOrgSettings.org_type || "Organization"}
                    options={(() => {
                      const found = typeMap.find(
                        (t) => t.key === "Organization",
                      );
                      return (found ? found.subtypes : ["Organization"]).map(
                        (s) => ({
                          label: s,
                          value: s,
                        }),
                      );
                    })()}
                    onChange={(val) =>
                      setEditedOrgSettings((prev) => ({
                        ...prev,
                        org_type: val,
                      }))
                    }
                    __nextHasNoMarginBottom
                  />
                </div>

                <p style={{ fontSize: "13px", color: "#666", margin: 0 }}>
                  {__(
                    "Set the default schema.org subtype for each post type. Individual posts can be overridden below.",
                    "gregius-optimizer",
                  )}
                </p>

                {postTypes.length > 0 && (
                  <div
                    style={{
                      maxHeight: "300px",
                      overflowY: "auto",
                      display: "flex",
                      flexDirection: "column",
                      gap: "0.5rem",
                    }}
                  >
                    {postTypes.map((pt) => {
                      const slug = pt.name || pt.slug;
                      const subtype = getDefaultForPostType(slug);
                      const parent = getTypeForSubtype(subtype);
                      const subtypes = getSubtypesForType(parent);
                      return (
                        <div
                          key={slug}
                          style={{
                            display: "grid",
                            gridTemplateColumns: "1fr 130px 1fr",
                            gap: "0.5rem",
                            alignItems: "center",
                            fontSize: "13px",
                          }}
                        >
                          <span style={{ fontWeight: 600 }}>
                            {pt.label || pt.name}
                          </span>
                          <SelectControl
                            value={parent}
                            options={typeMap.map((t) => ({
                              label: t.label,
                              value: t.key,
                            }))}
                            onChange={(newType) => {
                              const subs = getSubtypesForType(newType);
                              handleDefaultChange(slug, subs[0]);
                            }}
                            __nextHasNoMarginBottom
                          />
                          <SelectControl
                            value={subtype}
                            options={subtypes.map((s) => ({
                              label: s,
                              value: s,
                            }))}
                            onChange={(val) =>
                              handleDefaultChange(slug, val)
                            }
                            __nextHasNoMarginBottom
                          />
                        </div>
                      );
                    })}
                  </div>
                )}

                <hr style={{ margin: 0, border: "none", borderTop: "1px solid #e0e0e0" }} />

                {supportsCustomFields && (
                  <>
                    <h2 style={{ margin: 0 }}>
                      {__("Current Document", "gregius-optimizer")}
                    </h2>

                    <div
                      style={{
                        display: "flex",
                        flexDirection: "column",
                        gap: "0.5rem",
                        fontSize: "13px",
                      }}
                    >
                      <div
                        style={{
                          display: "grid",
                          gridTemplateColumns: "1fr 130px 1fr",
                          gap: "0.5rem",
                          alignItems: "center",
                        }}
                      >
                        <span style={{ fontWeight: 600 }}>
                          {__("Type", "gregius-optimizer")}
                        </span>
                        <SelectControl
                          value={perPostType}
                          options={typeMap.map((t) => ({
                            label: t.label,
                            value: t.key,
                          }))}
                          onChange={handlePostTypeChange}
                          __nextHasNoMarginBottom
                        />
                        <SelectControl
                          value={perPostSubtype}
                          options={getSubtypesForType(perPostType).map(
                            (s) => ({
                              label: s,
                              value: s,
                            }),
                          )}
                          onChange={handlePostSubtypeChange}
                          __nextHasNoMarginBottom
                        />
                      </div>
                    </div>
                  </>
                )}

                <div
                  style={{
                    display: "flex",
                    gap: "0.5rem",
                    alignItems: "center",
                  }}
                >
                  <Button
                    variant="primary"
                    onClick={handleUpdate}
                    disabled={isSaving}
                  >
                    {isSaving
                      ? __("Saving…", "gregius-optimizer")
                      : __("Update", "gregius-optimizer")}
                  </Button>
                  <Button
                    variant="secondary"
                    onClick={handleReset}
                    disabled={isSaving || !hasGlobalOverrides}
                  >
                    {__("Reset to defaults", "gregius-optimizer")}
                  </Button>
                </div>

                {error && <Notice status="error" isDismissible onRemove={() => setError("")}>{error}</Notice>}
                {success && <Notice status="success" isDismissible onRemove={() => setSuccess("")}>{success}</Notice>}

                <h2 style={{ margin: 0 }}>
                  {__("Preview", "gregius-optimizer")}
                </h2>

                {previewLoading ? (
                  <div style={{ textAlign: "center", padding: "1rem" }}>
                    <Spinner />
                  </div>
                ) : schemaPreview ? (
                  <div style={{ position: "relative" }}>
                    <pre
                      style={{
                        border: "1px solid #ddd",
                        borderRadius: "4px",
                        padding: "1rem",
                        whiteSpace: "pre-wrap",
                        wordBreak: "break-word",
                        fontSize: "13px",
                        lineHeight: "1.5",
                        overflow: "auto",
                        background: "#f8f8f8",
                        margin: 0,
                      }}
                    >
                      {JSON.stringify(schemaPreview, null, 2)}
                    </pre>
                    <button
                      onClick={() => {
                        navigator.clipboard
                          .writeText(
                            JSON.stringify(schemaPreview, null, 2),
                          )
                          .then(() => {
                            setCopied(true);
                            setTimeout(() => setCopied(false), 2000);
                          });
                      }}
                      style={{
                        position: "absolute",
                        top: "1em",
                        right: "1em",
                        border: "none",
                        borderRadius: "50%",
                        cursor: "pointer",
                        background: "#f0f0f0",
                        padding: "4px",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                      }}
                      title={__(
                        "Copy to clipboard",
                        "gregius-optimizer",
                      )}
                    >
                      <CopyIcon fill="#666" />
                    </button>
                  </div>
                ) : null}

                {copied && (
                  <SnackbarList
                    notices={[
                      {
                        content: __(
                          "Copied to clipboard",
                          "gregius-optimizer",
                        ),
                        explicitDismiss: false,
                        id: "gg-copy-schema",
                      },
                    ]}
                    onRemove={() => setCopied(false)}
                  />
                )}
              </>
            )}
          </div>
        </Modal>
      )}
    </>
  );
};

registerPlugin("gg-optimizer-schema-settings-sidebar", {
  render: SchemaSettingsSidebar,
});
