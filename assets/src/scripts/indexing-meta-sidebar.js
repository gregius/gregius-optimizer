import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel } from "@wordpress/edit-post";
import { useState, useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import apiFetch from "@wordpress/api-fetch";
import {
  Button,
  ExternalLink,
  Modal,
  Notice,
  PanelRow,
  Spinner,
  ToggleControl,
} from "@wordpress/components";

const SitemapSidebar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [settings, setSettings] = useState({});
  const [editedSettings, setEditedSettings] = useState({});
  const [postTypes, setPostTypes] = useState([]);
  const [taxonomies, setTaxonomies] = useState([]);
  const [users, setUsers] = useState([]);
  const [sitemapUrl, setSitemapUrl] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [featureEnabled, setFeatureEnabled] = useState( true );

  useEffect(() => {
    if (!isOpen) return;
    setIsLoading(true);
    setError("");
    setSuccess("");

    apiFetch({ path: "/gg-optimizer/v1/sitemap-settings" })
      .then((data) => {
        let s = data.settings || {};
        if (Array.isArray(s) && s.length === 0) {
          s = {};
        }
        setSettings(s);
        setEditedSettings(JSON.parse(JSON.stringify(s)));
        setPostTypes(data.post_types || []);
        setTaxonomies(data.taxonomies || []);
        setUsers(data.users || []);
        setSitemapUrl(data.sitemap_url || "");
      })
      .catch(() => {
        setError(
          __("Failed to load sitemap settings.", "gregius-optimizer"),
        );
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [isOpen]);

  useEffect( () => {
    if ( ! isOpen ) return;
    apiFetch( { path: '/gg-optimizer/v1/feature-toggles' } )
      .then( ( data ) => {
        if ( data && typeof data.sitemap === 'boolean' ) {
          setFeatureEnabled( data.sitemap );
        }
      } )
      .catch( () => {} );
  }, [isOpen] );

  const getPostTypeDefault = (slug) => {
    if (
      editedSettings.post_types &&
      editedSettings.post_types[slug] !== undefined
    ) {
      return editedSettings.post_types[slug];
    }
    return true;
  };

  const getTaxonomyDefault = (slug) => {
    if (
      editedSettings.taxonomies &&
      editedSettings.taxonomies[slug] !== undefined
    ) {
      return editedSettings.taxonomies[slug];
    }
    if (slug === "category" || slug === "post_tag") {
      return false;
    }
    return true;
  };

  const handlePostTypeChange = (slug, value) => {
    setEditedSettings((prev) => ({
      ...prev,
      post_types: {
        ...(prev.post_types || {}),
        [slug]: value,
      },
    }));
  };

  const handleTaxonomyChange = (slug, value) => {
    setEditedSettings((prev) => ({
      ...prev,
      taxonomies: {
        ...(prev.taxonomies || {}),
        [slug]: value,
      },
    }));
  };

  const handleAuthorsChange = (value) => {
    setEditedSettings((prev) => ({ ...prev, authors: value }));
  };

  const isUserIncluded = (userId) => {
    const excluded = editedSettings.excluded_users || [];
    return !excluded.includes(userId);
  };

  const handleUserChange = (userId, value) => {
    setEditedSettings((prev) => {
      const excluded = [...(prev.excluded_users || [])];
      if (value) {
        return { ...prev, excluded_users: excluded.filter((id) => id !== userId) };
      }
      if (!excluded.includes(userId)) {
        excluded.push(userId);
      }
      return { ...prev, excluded_users: excluded };
    });
  };

  const hasSettingsChanged =
    JSON.stringify(editedSettings) !== JSON.stringify(settings);

  const handleUpdate = () => {
    apiFetch( {
      path: '/gg-optimizer/v1/feature-toggles',
      method: 'POST',
      data: { toggles: { sitemap: featureEnabled } },
    } );

    if ( ! hasSettingsChanged ) {
      setSuccess(__("Settings updated.", "gregius-optimizer"));
      return;
    }
    setIsSaving(true);
    apiFetch({
      path: "/gg-optimizer/v1/sitemap-settings",
      method: "POST",
      data: { settings: editedSettings },
    })
      .then(() => {
        setSettings(JSON.parse(JSON.stringify(editedSettings)));
        setSuccess(__("Settings updated.", "gregius-optimizer"));
      })
      .catch(() => {
        setError(__("Failed to save settings.", "gregius-optimizer"));
      })
      .finally(() => {
        setIsSaving(false);
      });
  };

  const handleReset = async () => {
    setIsSaving(true);
    setError("");
    setSuccess("");
    try {
      await apiFetch({
        path: "/gg-optimizer/v1/sitemap-settings",
        method: "POST",
        data: { settings: {} },
      });
      const data = await apiFetch({ path: "/gg-optimizer/v1/sitemap-settings" });
      let s = data.settings || {};
      if (Array.isArray(s) && s.length === 0) {
        s = {};
      }
      setSettings(s);
      setEditedSettings(JSON.parse(JSON.stringify(s)));
      setSuccess(__("Reset to defaults.", "gregius-optimizer"));
    } catch {
      setError(__("Failed to reset settings.", "gregius-optimizer"));
    } finally {
      setIsSaving(false);
    }
  };

  const hasSavedOverrides = Object.keys(settings).length > 0;

  return (
    <>
      <PluginDocumentSettingPanel
        name="gg-optimizer-indexing-meta-sidebar"
        title={__("Sitemap", "gregius-optimizer")}
        className="gg-optimizer-indexing-meta-sidebar"
        initialOpen={true}
      >
        <PanelRow>
          <div>
            <p
              style={{
                fontSize: "13px",
                color: "#666",
              }}
            >
              {__(
                "Configure sitemap inclusions and indexing rules.",
                "gregius-optimizer",
              )}{" "}
              <ExternalLink href="https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview">
                {__("Learn more about Sitemaps", "gregius-optimizer")}
              </ExternalLink>
            </p>
            <Button
              variant="secondary"
              onClick={() => setIsOpen(true)}
            >
              {__("Settings", "gregius-optimizer")}
            </Button>
          </div>
        </PanelRow>
      </PluginDocumentSettingPanel>

      {isOpen && (
        <Modal
          title={__("Sitemap", "gregius-optimizer")}
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
                <ToggleControl
                  label={ __( 'Enable Sitemap', 'gregius-optimizer' ) }
                  checked={ featureEnabled }
                  onChange={ ( value ) => setFeatureEnabled( value ) }
                  __nextHasNoMarginBottom
                />

                <div
                  className={
                    featureEnabled ? '' : 'gg-optimizer-feature-disabled'
                  }
                >
                  <p>
                  {__(
                    "Edit the content of your site's wp-sitemap.xml file served at",
                    "gregius-optimizer",
                  )}{" "}
                  {sitemapUrl && (
                    <ExternalLink href={sitemapUrl}>
                      {sitemapUrl}
                    </ExternalLink>
                  )}
                  {!sitemapUrl && "/wp-sitemap.xml"}.
                </p>

                <h2>
                  {__("Global Settings", "gregius-optimizer")}
                </h2>

                <div>
                  <h3 style={{ fontSize: "13px" }}>
                    {__("Post types", "gregius-optimizer")}
                  </h3>
                  <div
                    style={{
                      display: "flex",
                      flexDirection: "column",
                      gap: "0.25rem",
                    }}
                  >
                    {postTypes.map((pt) => (
                      <ToggleControl
                        key={pt.slug}
                        label={pt.label}
                        checked={getPostTypeDefault(pt.slug)}
                        onChange={(val) =>
                          handlePostTypeChange(pt.slug, val)
                        }
                        __nextHasNoMarginBottom
                      />
                    ))}
                  </div>
                </div>

                <div>
                  <h3 style={{ fontSize: "13px" }}>
                    {__("Taxonomies", "gregius-optimizer")}
                  </h3>
                  <div
                    style={{
                      display: "flex",
                      flexDirection: "column",
                      gap: "0.25rem",
                    }}
                  >
                    {taxonomies.map((tax) => (
                      <ToggleControl
                        key={tax.slug}
                        label={`${tax.label} (${tax.slug})`}
                        checked={getTaxonomyDefault(tax.slug)}
                        onChange={(val) =>
                          handleTaxonomyChange(tax.slug, val)
                        }
                        __nextHasNoMarginBottom
                      />
                    ))}
                  </div>
                </div>

                <div>
                  <h3 style={{ fontSize: "13px" }}>
                    {__("Author sitemap", "gregius-optimizer")}
                  </h3>
                  <ToggleControl
                    label={__(
                      "Include author page in sitemap",
                      "gregius-optimizer",
                    )}
                    checked={!!editedSettings.authors}
                    onChange={handleAuthorsChange}
                    __nextHasNoMarginBottom
                  />
                  {editedSettings.authors && users.length > 0 && (
                    <div
                      style={{
                        marginTop: "0.25rem",
                        paddingLeft: "1rem",
                        borderLeft: "2px solid #e0e0e0",
                        display: "flex",
                        flexDirection: "column",
                        gap: "0.25rem",
                      }}
                    >
                      {users.map((user) => (
                        <ToggleControl
                          key={user.id}
                          label={`${user.display_name} (${user.id})`}
                          checked={isUserIncluded(user.id)}
                          onChange={(val) => handleUserChange(user.id, val)}
                          __nextHasNoMarginBottom
                        />
                      ))}
                    </div>
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
                    disabled={isSaving || !hasSavedOverrides}
                  >
                    {__("Reset to defaults", "gregius-optimizer")}
                  </Button>
                </div>

                {error && <Notice status="error" isDismissible onRemove={() => setError("")}>{error}</Notice>}
                {success && <Notice status="success" isDismissible onRemove={() => setSuccess("")}>{success}</Notice>}
              </>
            )}
          </div>
        </Modal>
      )}
    </>
  );
};

registerPlugin("gg-optimizer-indexing-meta-sidebar", {
  render: SitemapSidebar,
});
