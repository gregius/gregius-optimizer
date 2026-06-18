import { PluginDocumentSettingPanel } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";
import { registerPlugin } from "@wordpress/plugins";
import { useSelect, useDispatch } from "@wordpress/data";
import { useState, useCallback, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import {
  Button,
  ExternalLink,
  Modal,
  Notice,
  PanelRow,
  Dropdown,
  MenuGroup,
  MenuItem,
  ToggleControl,
} from "@wordpress/components";
import { RichText, MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";

const META = {
  googleTitle: "_gg_optimizer_google_title",
  googleDescription: "_gg_optimizer_google_description",
  ogTitle: "_gg_optimizer_og_title",
  ogDescription: "_gg_optimizer_og_description",
  ogImage: "_gg_optimizer_og_image",
  twitterTitle: "_gg_optimizer_twitter_title",
  twitterDescription: "_gg_optimizer_twitter_description",
  twitterImage: "_gg_optimizer_twitter_image",
  commonImage: "_gg_optimizer_meta_image",
};

const LIMITS = {
  googleTitle: 60,
  googleDescription: 160,
  ogTitle: 55,
  ogDescription: 65,
  twitterTitle: 70,
  twitterDescription: 200,
};

const stripHtml = (text) => {
  if (!text) return "";
  return text.replace(/<[^>]*>/g, "").trim();
};

const charCount = (text) => stripHtml(text || "").length;

const Counter = ({ current, max }) => {
  const over = current > max;
  return (
    <span
      style={{
        fontSize: "11px",
        color: over ? "#cc0000" : "#666",
        marginLeft: "auto",
        whiteSpace: "nowrap",
      }}
    >
      {current} / {max}
    </span>
  );
};

const asEditedString = (value) => {
  if ("string" === typeof value) return value;
  if (value && "string" === typeof value.raw) return value.raw;
  return "";
};

const GlobeIcon = () => (
  <svg focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14">
    <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" />
  </svg>
);

const KebabIcon = () => (
  <svg focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18">
    <path fill="currentColor" d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
  </svg>
);

const formatUrl = (permalink) => {
  if (!permalink) return "";
  try {
    const url = new URL(permalink);
    const path = url.pathname.replace(/\/$/, "").replace(/^\//, "");
    if (path) {
      return "https://" + url.hostname + " › " + path;
    }
    return "https://" + url.hostname;
  } catch {
    return permalink;
  }
};

const GoogleCard = ({ title, description, onTitleChange, onDescriptionChange, fallbackTitle, fallbackDesc, url }) => {
  const titleCount = charCount(title || fallbackTitle);
  const descCount = charCount(description || fallbackDesc);
  return (
  <div style={{ display: "flex", flexDirection: "column", gap: "0.25rem", padding: "1em", border: "1px solid #ddd", borderRadius: "8px" }}>
    <div
      style={{
        display: "grid",
        gridTemplateColumns: "36px 1fr",
        gap: "0 0.75rem",
        alignItems: "center",
      }}
    >
      <div
        style={{
          gridRow: "span 2",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          width: "36px",
          height: "36px",
          borderRadius: "50%",
          backgroundColor: "#e8eaed",
        }}
      >
        <GlobeIcon />
      </div>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <span style={{ fontSize: "14px", color: "#333" }}>{window.location.hostname || "example.com"}</span>
      </div>
      <div style={{ display: "flex", alignItems: "center", gap: ".25rem" }}>
        <span style={{ color: "rgb(0, 102, 33)", fontSize: "12px" }}>{url || "https://" + (window.location.hostname || "example.com")}</span>
        <svg focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path></svg>
      </div>
    </div>
    <div style={{ display: "flex", alignItems: "center" }}>
    <RichText
      tagName="div"
      value={title}
      onChange={onTitleChange}
      placeholder={fallbackTitle}
      aria-label={__("Google Search Title", "gregius-optimizer")}
      allowedFormats={[]}
      withoutInteractiveFormatting
      style={{
        flex: 1,
        color: "#1a0dab",
        fontSize: "20px",
        lineHeight: 1.3,
        padding: "0",
        cursor: "text",
      }}
    />
    <Counter current={titleCount} max={LIMITS.googleTitle} />
    </div>
    <div style={{ display: "flex", gap: "0.5rem", alignItems: "flex-start" }}>
      <RichText
        tagName="div"
        value={description}
        onChange={onDescriptionChange}
        placeholder={fallbackDesc}
        aria-label={__("Google Search Description", "gregius-optimizer")}
        allowedFormats={[]}
        withoutInteractiveFormatting
        style={{
          flex: 1,
          color: "#545454",
          fontSize: "14px",
          lineHeight: 1.58,
          padding: "0",
          cursor: "text",
        }}
      />
      <Counter current={descCount} max={LIMITS.googleDescription} />
    </div>
  </div>
  );
};

const OGCard = ({ title, description, imageId, onTitleChange, onDescriptionChange, onImageChange, onImageRemove, fallbackTitle, fallbackDesc, imageUrl, imageAlt, url }) => {
  const titleCount = charCount(title || fallbackTitle);
  const descCount = charCount(description || fallbackDesc);
  return (
  <div style={{ display: "flex", flexDirection: "column", gap: "0.5rem", border: "1px solid #ddd", borderRadius: "8px", overflow: "hidden" }}>
    <MediaUploadCheck>
      <MediaUpload
        onSelect={(media) => {
          if (media && media.id) {
            onImageChange(media.id);
          }
        }}
        allowedTypes={["image"]}
        value={imageId || ""}
        render={({ open }) => (
          <div style={{ position: "relative" }}>
            <div
              style={{
                borderBottom: "1px solid #ddd",
                overflow: "hidden",
                cursor: "pointer",
              }}
              onClick={open}
            >
              {imageUrl ? (
                <img
                  src={imageUrl}
                  alt={imageAlt || ""}
                  style={{
                    width: "100%",
                    height: "auto",
                    display: "block",
                    maxHeight: "200px",
                    objectFit: "cover",
                  }}
                />
              ) : (
                <div
                  style={{
                    padding: "2rem",
                    textAlign: "center",
                    color: "#666",
                    fontSize: "13px",
                    background: "#fafafa",
                  }}
                >
                  {__("Choose image", "gregius-optimizer")}
                </div>
              )}
            </div>
            <div style={{ position: "absolute", top: "1em", right: "1em" }}>
              <Dropdown
                renderToggle={({ isOpen, onToggle }) => (
                  <Button
                    icon={KebabIcon}
                    onClick={onToggle}
                    aria-expanded={isOpen}
                    style={{
                      width: "28px",
                      height: "28px",
                      borderRadius: "50%",
                      padding: "0",
                      minWidth: "28px",
                      backgroundColor: "rgba(0,0,0,0.45)",
                      justifyContent: "center",
                      alignItems: "center",
                      display: "flex",
                      color: "#fff",
                    }}
                  />
                )}
                renderContent={({ onClose }) => (
                  <MenuGroup>
                    <MenuItem onClick={() => { open(); onClose(); }}>
                      {imageUrl ? __("Replace", "gregius-optimizer") : __("Choose image", "gregius-optimizer")}
                    </MenuItem>
                    <MenuItem onClick={() => { onImageRemove(); onClose(); }} disabled={!imageUrl}>
                      {__("Remove", "gregius-optimizer")}
                    </MenuItem>
                  </MenuGroup>
                )}
              />
            </div>
          </div>
        )}
      />
    </MediaUploadCheck>
    <div style={{ display: "flex", flexDirection: "column", gap: ".25em", padding: "1em" }}>
    <div style={{ fontSize: "11px", textTransform: "uppercase", color: "#999" }}>
      {window.location.hostname || "example.com"}
    </div>
    <div style={{ display: "flex", alignItems: "center" }}>
    <RichText
      tagName="div"
      value={title}
      onChange={onTitleChange}
      placeholder={fallbackTitle}
      aria-label={__("Open Graph Title", "gregius-optimizer")}
      allowedFormats={[]}
      withoutInteractiveFormatting
      style={{
        flex: 1,
        fontWeight: 600,
        fontSize: "16px",
        lineHeight: 1.4,
        padding: "0",
        cursor: "text",
      }}
    />
    <Counter current={titleCount} max={LIMITS.ogTitle} />
    </div>
    <div style={{ display: "flex", gap: "0.5rem", alignItems: "flex-start" }}>
      <RichText
        tagName="div"
        value={description}
        onChange={onDescriptionChange}
        placeholder={fallbackDesc}
        aria-label={__("Open Graph Description", "gregius-optimizer")}
        allowedFormats={[]}
        withoutInteractiveFormatting
        style={{
          flex: 1,
          color: "#545454",
          fontSize: "14px",
          lineHeight: 1.4,
          padding: "0",
          cursor: "text",
        }}
      />
      <Counter current={descCount} max={LIMITS.ogDescription} />
    </div>
    </div>
  </div>
  );
};

const TwitterCard = ({ title, description, imageId, onTitleChange, onDescriptionChange, onImageChange, onImageRemove, fallbackTitle, fallbackDesc, imageUrl, imageAlt, url }) => {
  const titleCount = charCount(title || fallbackTitle);
  const descCount = charCount(description || fallbackDesc);
  return (
  <div style={{ display: "flex", flexDirection: "column", gap: "0.5rem", border: "1px solid #ddd", borderRadius: "8px", overflow: "hidden" }}>
    <MediaUploadCheck>
      <MediaUpload
        onSelect={(media) => {
          if (media && media.id) {
            onImageChange(media.id);
          }
        }}
        allowedTypes={["image"]}
        value={imageId || ""}
        render={({ open }) => (
          <div style={{ position: "relative" }}>
            <div
              style={{
                borderBottom: "1px solid #ddd",
                overflow: "hidden",
                cursor: "pointer",
              }}
              onClick={open}
            >
              {imageUrl ? (
                <img
                  src={imageUrl}
                  alt={imageAlt || ""}
                  style={{
                    width: "100%",
                    height: "auto",
                    display: "block",
                    maxHeight: "200px",
                    objectFit: "cover",
                  }}
                />
              ) : (
                <div
                  style={{
                    padding: "2rem",
                    textAlign: "center",
                    color: "#666",
                    fontSize: "13px",
                    background: "#fafafa",
                  }}
                >
                  {__("Choose image", "gregius-optimizer")}
                </div>
              )}
            </div>
            <div style={{ position: "absolute", top: "1em", right: "1em" }}>
              <Dropdown
                renderToggle={({ isOpen, onToggle }) => (
                  <Button
                    icon={KebabIcon}
                    onClick={onToggle}
                    aria-expanded={isOpen}
                    style={{
                      width: "28px",
                      height: "28px",
                      borderRadius: "50%",
                      padding: "0",
                      minWidth: "28px",
                      backgroundColor: "rgba(0,0,0,0.45)",
                      justifyContent: "center",
                      alignItems: "center",
                      display: "flex",
                      color: "#fff",
                    }}
                  />
                )}
                renderContent={({ onClose }) => (
                  <MenuGroup>
                    <MenuItem onClick={() => { open(); onClose(); }}>
                      {imageUrl ? __("Replace", "gregius-optimizer") : __("Choose image", "gregius-optimizer")}
                    </MenuItem>
                    <MenuItem onClick={() => { onImageRemove(); onClose(); }} disabled={!imageUrl}>
                      {__("Remove", "gregius-optimizer")}
                    </MenuItem>
                  </MenuGroup>
                )}
              />
            </div>
          </div>
        )}
      />
    </MediaUploadCheck>
    <div style={{ display: "flex", flexDirection: "column", gap: ".25em", padding: "1em" }}>
    <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
      <span style={{ fontSize: "12px", color: "#333" }}>{window.location.hostname || "example.com"}</span>
    </div>
    <div style={{ display: "flex", alignItems: "center" }}>
    <RichText
      tagName="div"
      value={title}
      onChange={onTitleChange}
      placeholder={fallbackTitle}
      aria-label={__("Twitter Title", "gregius-optimizer")}
      allowedFormats={[]}
      withoutInteractiveFormatting
      style={{
        flex: 1,
        fontSize: "15px",
        lineHeight: 1.4,
        padding: "0",
        cursor: "text",
      }}
    />
    <Counter current={titleCount} max={LIMITS.twitterTitle} />
    </div>
    <div style={{ display: "flex", gap: "0.5rem", alignItems: "flex-start" }}>
      <RichText
        tagName="div"
        value={description}
        onChange={onDescriptionChange}
        placeholder={fallbackDesc}
        aria-label={__("Twitter Description", "gregius-optimizer")}
        allowedFormats={[]}
        withoutInteractiveFormatting
        style={{
          flex: 1,
          color: "#536471",
          fontSize: "12px",
          lineHeight: 1.4,
          padding: "0",
          cursor: "text",
        }}
      />
      <Counter current={descCount} max={LIMITS.twitterDescription} />
    </div>
    </div>
  </div>
  );
};

const SocialCardsSidebar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [featureEnabled, setFeatureEnabled] = useState( true );

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
  const meta = useSelect(
    (select) => select("core/editor").getEditedPostAttribute("meta") || {},
    [],
  );
  const editedTitle = useSelect(
    (select) => asEditedString(select("core/editor").getEditedPostAttribute("title")),
    [],
  );
  const editedExcerpt = useSelect(
    (select) => asEditedString(select("core/editor").getEditedPostAttribute("excerpt")),
    [],
  );
  const editedContent = useSelect(
    (select) => asEditedString(select("core/editor").getEditedPostAttribute("content")),
    [],
  );
  const editedFeaturedMedia = useSelect(
    (select) => select("core/editor").getEditedPostAttribute("featured_media") || 0,
    [],
  );
  const permalink = useSelect(
    (select) => select("core/editor").getPermalink(),
    [],
  );

  const ogImageId = meta[META.ogImage] || "";
  const twitterImageId = meta[META.twitterImage] || "";
  const commonImageId = meta._gg_optimizer_meta_image || "";

  const ogMedia = useSelect(
    (select) => {
      if (!ogImageId) return null;
      return select("core").getMedia(ogImageId) || null;
    },
    [ogImageId],
  );

  const twitterMedia = useSelect(
    (select) => {
      if (!twitterImageId) return null;
      return select("core").getMedia(twitterImageId) || null;
    },
    [twitterImageId],
  );

  const commonMedia = useSelect(
    (select) => {
      if (!commonImageId) return null;
      return select("core").getMedia(commonImageId) || null;
    },
    [commonImageId],
  );

  const featuredMedia = useSelect(
    (select) => {
      if (!editedFeaturedMedia) return null;
      return select("core").getMedia(editedFeaturedMedia) || null;
    },
    [editedFeaturedMedia],
  );

  const { editPost } = useDispatch("core/editor");

  const fallbackTitle = editedTitle;
  const fallbackDescription = editedExcerpt || stripHtml(editedContent).split(/\s+/).slice(0, 20).join(" ");

  const commonImageUrl = commonMedia?.source_url || null;
  const commonImageAlt = commonMedia?.alt_text || "";

  const ogImageUrl = ogMedia?.source_url || featuredMedia?.source_url || commonMedia?.source_url || null;
  const ogImageAlt = ogMedia?.alt_text || featuredMedia?.alt_text || commonMedia?.alt_text || "";

  const twitterImageUrl = twitterMedia?.source_url || featuredMedia?.source_url || commonMedia?.source_url || null;
  const twitterImageAlt = twitterMedia?.alt_text || featuredMedia?.alt_text || commonMedia?.alt_text || "";

  const updateMeta = useCallback((key, value) => {
    editPost({ meta: { ...meta, [key]: value } });
  }, [meta, editPost]);

  useEffect( () => {
    if ( ! isOpen ) return;
    apiFetch( { path: '/gg-optimizer/v1/feature-toggles' } )
      .then( ( data ) => {
        if ( data && typeof data.social_cards === 'boolean' ) {
          setFeatureEnabled( data.social_cards );
        }
      } )
      .catch( () => {} );
  }, [isOpen] );

  const hasOverrides = Object.values(META).some((key) => meta[key]);

  const saveSettings = () => {
    setError("");
    setSuccess(__("Settings updated.", "gregius-optimizer"));

    apiFetch( {
      path: '/gg-optimizer/v1/feature-toggles',
      method: 'POST',
      data: { toggles: { social_cards: featureEnabled } },
    } );
  };

  const resetOverrides = () => {
    editPost({
      meta: {
        ...meta,
        [META.googleTitle]: "",
        [META.googleDescription]: "",
        [META.ogTitle]: "",
        [META.ogDescription]: "",
        [META.ogImage]: "",
        [META.twitterTitle]: "",
        [META.twitterDescription]: "",
        [META.twitterImage]: "",
        [META.commonImage]: "",
      },
    });
  };

  return (
    <>
      <PluginDocumentSettingPanel
        name="gg-optimizer-smo-preview-sidebar"
        title={__("Social Cards", "gregius-optimizer")}
        className="gg-optimizer-smo-preview-sidebar"
        initialOpen={false}
      >
        <PanelRow>
          <div>
            <p>
              {__(
                "Adjust metadata for search and social platforms. Changes affect Open Graph and Twitter Cards.",
                "gregius-optimizer",
              )}{" "}
              <ExternalLink href="https://developers.google.com/search/docs/appearance/structured-data/search-gallery">
                {__("Learn about Search Snippets", "gregius-optimizer")}
              </ExternalLink>
              {" | "}
              <ExternalLink href="https://ogp.me/">
                {__("Open Graph", "gregius-optimizer")}
              </ExternalLink>
              {" | "}
              <ExternalLink href="https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards">
                {__("Twitter Cards", "gregius-optimizer")}
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
          title={__("Social Cards", "gregius-optimizer")}
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
            <ToggleControl
              label={ __( 'Enable Social Cards', 'gregius-optimizer' ) }
              checked={ featureEnabled }
              onChange={ ( value ) => setFeatureEnabled( value ) }
              __nextHasNoMarginBottom
            />

            <div
              className={
                featureEnabled ? '' : 'gg-optimizer-feature-disabled'
              }
            >
              <h2>{__("Global Image", "gregius-optimizer")}</h2>

            <div style={{ display: "grid", gridTemplateColumns: "150px 1fr", gap: "1rem", alignItems: "start" }}>
              <div>
                <MediaUploadCheck>
                  <MediaUpload
                    onSelect={(media) => {
                      if (media && media.id) {
                        updateMeta(META.commonImage, String(media.id));
                      }
                    }}
                    allowedTypes={["image"]}
                    value={commonImageId || ""}
                    render={({ open }) => (
                      <div style={{ position: "relative" }}>
                        <div
                          style={{
                            border: "1px solid #ddd",
                            borderRadius: "4px",
                            overflow: "hidden",
                            cursor: "pointer",
                            width: "150px",
                            height: "150px",
                          }}
                          onClick={open}
                        >
                          {commonImageUrl ? (
                            <img
                              src={commonImageUrl}
                              alt={commonImageAlt || ""}
                              style={{
                                width: "150px",
                                height: "150px",
                                display: "block",
                                objectFit: "cover",
                              }}
                            />
                          ) : (
                            <div
                              style={{
                                width: "150px",
                                height: "150px",
                                display: "flex",
                                alignItems: "center",
                                justifyContent: "center",
                                textAlign: "center",
                                color: "#666",
                                fontSize: "13px",
                                background: "#fafafa",
                              }}
                            >
                              {__("Choose image", "gregius-optimizer")}
                            </div>
                          )}
                        </div>
                        <div style={{ position: "absolute", top: "1em", right: "1em" }}>
                          <Dropdown
                            renderToggle={({ isOpen, onToggle }) => (
                              <Button
                                icon={KebabIcon}
                                onClick={onToggle}
                                aria-expanded={isOpen}
                                style={{
                                  width: "28px",
                                  height: "28px",
                                  borderRadius: "50%",
                                  padding: "0",
                                  minWidth: "28px",
                                  backgroundColor: "rgba(0,0,0,0.45)",
                                  justifyContent: "center",
                                  alignItems: "center",
                                  display: "flex",
                                  color: "#fff",
                                }}
                              />
                            )}
                            renderContent={({ onClose }) => (
                              <MenuGroup>
                                <MenuItem onClick={() => { open(); onClose(); }}>
                                  {commonImageUrl ? __("Replace", "gregius-optimizer") : __("Choose image", "gregius-optimizer")}
                                </MenuItem>
                                <MenuItem onClick={() => { updateMeta(META.commonImage, ""); onClose(); }} disabled={!commonImageUrl}>
                                  {__("Remove", "gregius-optimizer")}
                                </MenuItem>
                              </MenuGroup>
                            )}
                          />
                        </div>
                      </div>
                    )}
                  />
                </MediaUploadCheck>
              </div>
              <div style={{ fontSize: "13px", color: "#666", lineHeight: 1.5 }}>
                {__("Set a default social image for all platforms. When a card doesn't have its own image, this one is used instead. If left empty, we'll use your post's featured image.", "gregius-optimizer")}
              </div>
            </div>

            <h2>{__("Search Snippet", "gregius-optimizer")}</h2>

            <GoogleCard
              title={meta[META.googleTitle] || ""}
              description={meta[META.googleDescription] || ""}
              onTitleChange={(val) => updateMeta(META.googleTitle, val)}
              onDescriptionChange={(val) => updateMeta(META.googleDescription, val)}
              fallbackTitle={fallbackTitle}
              fallbackDesc={fallbackDescription}
              url={formatUrl(permalink)}
            />

            <h2>{__("Open Graph", "gregius-optimizer")}</h2>

            <OGCard
              title={meta[META.ogTitle] || ""}
              description={meta[META.ogDescription] || ""}
              imageId={ogImageId}
              imageUrl={ogImageUrl}
              imageAlt={ogImageAlt}
              onTitleChange={(val) => updateMeta(META.ogTitle, val)}
              onDescriptionChange={(val) => updateMeta(META.ogDescription, val)}
              onImageChange={(id) => updateMeta(META.ogImage, String(id))}
              onImageRemove={() => updateMeta(META.ogImage, "")}
              fallbackTitle={fallbackTitle}
              fallbackDesc={fallbackDescription}
              url={permalink}
            />

            <h2>{__("Twitter / X", "gregius-optimizer")}</h2>

            <TwitterCard
              title={meta[META.twitterTitle] || ""}
              description={meta[META.twitterDescription] || ""}
              imageId={twitterImageId}
              imageUrl={twitterImageUrl}
              imageAlt={twitterImageAlt}
              onTitleChange={(val) => updateMeta(META.twitterTitle, val)}
              onDescriptionChange={(val) => updateMeta(META.twitterDescription, val)}
              onImageChange={(id) => updateMeta(META.twitterImage, String(id))}
              onImageRemove={() => updateMeta(META.twitterImage, "")}
              fallbackTitle={fallbackTitle}
              fallbackDesc={fallbackDescription}
              url={permalink}
            />

            </div>

            <div
              style={{
                display: "flex",
                gap: "0.5rem",
                alignItems: "center",
              }}
            >
              <Button variant="primary" onClick={saveSettings}>
                {__("Update", "gregius-optimizer")}
              </Button>
              <Button
                variant="secondary"
                onClick={resetOverrides}
                disabled={!hasOverrides}
              >
                {__("Reset to defaults", "gregius-optimizer")}
              </Button>
            </div>

            {error && <Notice status="error" isDismissible onRemove={() => setError("")}>{error}</Notice>}
            {success && <Notice status="success" isDismissible onRemove={() => setSuccess("")}>{success}</Notice>}
          </div>
        </Modal>
      )}
    </>
  );
};

registerPlugin("gg-optimizer-social-cards-sidebar", { render: SocialCardsSidebar });
