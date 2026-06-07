import apiFetch from "@wordpress/api-fetch";
import { PluginDocumentSettingPanel } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";
import { registerPlugin } from "@wordpress/plugins";
import { useState, useEffect } from "@wordpress/element";
import {
  Button,
  ExternalLink,
  Modal,
  Notice,
  PanelRow,
  Spinner,
  TextareaControl,
} from "@wordpress/components";

const RobotsTxtSidebar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [content, setContent] = useState("");
  const [hasCustom, setHasCustom] = useState(false);
  const [robotsTxtUrl, setRobotsTxtUrl] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const lineCount = (content.match(/\n/g) || []).length + 1;

  useEffect(() => {
    if (!isOpen) return;
    setIsLoading(true);
    setError("");
    setSuccess("");

    apiFetch({ path: "/gg-optimizer/v1/robots-txt" })
      .then((data) => {
        const c = data.content || "";
        setContent(c);
        setHasCustom(data.has_custom || false);
        setRobotsTxtUrl(data.robots_txt_url || "");
      })
      .catch(() => {
        setError(
          __("Failed to load robots.txt settings.", "gregius-optimizer"),
        );
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [isOpen]);

  const save = async () => {
    setIsSaving(true);
    setError("");
    setSuccess("");
    try {
      await apiFetch({
        path: "/gg-optimizer/v1/robots-txt",
        method: "POST",
        data: { content },
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
    try {
      await apiFetch({
        path: "/gg-optimizer/v1/robots-txt",
        method: "POST",
        data: { content: "" },
      });
      const data = await apiFetch({ path: "/gg-optimizer/v1/robots-txt" });
      const c = data.content || "";
      setContent(c);
      setHasCustom(false);
      setSuccess(__("Reset to defaults.", "gregius-optimizer"));
    } catch (err) {
      setError(
        err?.message || __("Unable to reset settings.", "gregius-optimizer"),
      );
    } finally {
      setIsSaving(false);
    }
  };

  const closeModal = () => {
    setIsOpen(false);
    setError("");
    setSuccess("");
  };

  return (
    <>
      <PluginDocumentSettingPanel
        name="gg-optimizer-robots-txt"
        title={__("Robots", "gregius-optimizer")}
        className="gg-optimizer-robots-txt"
        initialOpen={false}
      >
        <PanelRow>
          <div>
            <p>
              {__("Edit your robots.txt directives.", "gregius-optimizer")}{" "}
              <ExternalLink href="https://developers.google.com/search/docs/crawling-indexing/robots/intro">
                {__("Learn more about robots.txt", "gregius-optimizer")}
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
          title={__("Robots", "gregius-optimizer")}
          onRequestClose={closeModal}
        >
          <div
            style={{
              overflowY: "auto",
              paddingRight: "8px",
              maxWidth: "800px",
              display: "flex",
              flexDirection: "column",
              gap: "1rem",
            }}
          >
            <p style={{ margin: 0 }}>
              {__(
                "Edit the content of your site's robots.txt file served at",
                "gregius-optimizer",
              )}{" "}
              {robotsTxtUrl && (
                <ExternalLink href={robotsTxtUrl}>
                  {robotsTxtUrl}
                </ExternalLink>
              )}
              {!robotsTxtUrl && "/robots.txt"}.
            </p>

            <div>
              {isLoading && <Spinner />}
              {!isLoading && (
                <TextareaControl
                  label={__("Content", "gregius-optimizer")}
                  value={content}
                  onChange={(value) => setContent(value)}
                  rows={Math.max(lineCount, 20)}
                  __nextHasNoMarginBottom
                />
              )}
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
          </div>
        </Modal>
      )}
    </>
  );
};

registerPlugin("gg-optimizer-robots-txt", { render: RobotsTxtSidebar });
