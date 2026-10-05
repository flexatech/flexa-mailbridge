/**
 * Bridge to the `flexaMailBridge` global published by Enqueue.php via
 * wp_localize_script.
 */

export type AppTheme = "light" | "dark";

export interface FieldDef {
    type: "string" | "int" | "bool" | "enum";
    secret?: boolean;
    enum?: string[];
    /** Enum rendered as an editable combobox: `enum` is suggestions, any typed value is accepted. */
    open?: boolean;
}

/** slug => (field => definition), from Settings::mailer_schema(). */
export type MailerSchema = Record<string, Record<string, FieldDef>>;

/** FormFlow cross-promotion state, from Enqueue::formflow_promo(). */
export interface FormFlowPromo {
    /** Already accounts for capability, dismissal, and FormFlow being installed. */
    show: boolean;
    /** One-click install link, or "" when the user may not install plugins. */
    installUrl: string;
    learnMoreUrl: string;
    iconUrl: string;
}

export interface PluginGlobal {
    restUrl: string;
    restNonce: string;
    namespace: string;
    version: string;
    pluginUrl: string;
    adminUrl: string;
    locale: string;
    theme: AppTheme;
    /** Whether the current user may change settings (manage_options). */
    canManageSettings: boolean;
    /** Per-mailer credential field schema for every registered mailer. */
    schema: MailerSchema;
    /** Sentinel a secret field echoes back when left unedited. */
    secretMask: string;
    /** Cross-promotion banner state; absent on older bundles. */
    formFlow?: FormFlowPromo;
}

declare global {
    interface Window {
        flexaMailBridge?: PluginGlobal;
    }
}

export function getPluginGlobal(): PluginGlobal {
    if (!window.flexaMailBridge) {
        throw new Error(
            "flexaMailBridge global missing - make sure Enqueue::enqueue_admin ran before this script.",
        );
    }
    return window.flexaMailBridge;
}
