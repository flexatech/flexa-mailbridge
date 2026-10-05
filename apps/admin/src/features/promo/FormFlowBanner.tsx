import { Download, ExternalLink, X } from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { __ } from "@/lib/i18n";
import { useUiStore } from "@/lib/store";
import { getPluginGlobal } from "@/lib/wp";
import { useDismissPromo } from "./usePromo";

/**
 * Cross-promotion for Flexa FormFlow, shown above the page header. PHP decides
 * visibility (`formFlow.show` already covers capability, dismissal and
 * FormFlow being installed); this component only renders and dismisses it.
 */
export function FormFlowBanner() {
    const { formFlow } = getPluginGlobal();
    const dismiss = useDismissPromo();
    const showToast = useUiStore((s) => s.showToast);
    const [hidden, setHidden] = useState(false);

    if (!formFlow?.show || hidden) {
        return null;
    }

    const onDismiss = () => {
        setHidden(true);
        dismiss.mutate(undefined, {
            onError: () => {
                setHidden(false);
                showToast(__("Could not hide the suggestion."), "error");
            },
        });
    };

    return (
        <div className="fs:px-6 fs:pt-6">
            <div className="fs:relative fs:flex fs:items-start fs:gap-4 fs:rounded-xl fs:border fs:border-slate-200 fs:bg-brand-50 fs:p-4 fs:shadow-sm fs:sm:p-5">
                <img
                    src={formFlow.iconUrl}
                    width={48}
                    height={48}
                    alt=""
                    decoding="async"
                    className="fs:h-12 fs:w-12 fs:shrink-0 fs:rounded-lg"
                />

                <div className="fs:min-w-0 fs:flex-1 fs:pr-8">
                    <div className="fs:flex fs:flex-wrap fs:items-center fs:gap-2">
                        <h2 className="fs:text-base fs:font-semibold fs:text-slate-900">
                            {__(
                                "Add forms, email templates and workflows with Flexa FormFlow",
                            )}
                        </h2>
                        <span className="fs:rounded-full fs:bg-white fs:px-2 fs:py-0.5 fs:text-xs fs:font-medium fs:text-brand-700">
                            {__("Free plugin")}
                        </span>
                    </div>

                    <p className="fs:mt-1.5 fs:max-w-3xl fs:text-sm fs:text-slate-600">
                        {__(
                            "FormFlow is our free form builder with a visual email designer and a workflow engine built in. Drag fields onto the canvas, design the emails they trigger, then add conditional steps that run on each submission. MailBridge keeps handling the routing, logs and tracking.",
                        )}
                    </p>

                    <div className="fs:mt-3 fs:flex fs:flex-wrap fs:items-center fs:gap-2">
                        {formFlow.installUrl !== "" && (
                            <Button asChild size="sm">
                                <a href={formFlow.installUrl}>
                                    <Download
                                        className="fs:h-4 fs:w-4"
                                        aria-hidden
                                    />
                                    {__("Install FormFlow")}
                                </a>
                            </Button>
                        )}
                        <Button asChild variant="outline" size="sm">
                            <a
                                href={formFlow.learnMoreUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {__("Learn more")}
                                <ExternalLink
                                    className="fs:h-3.5 fs:w-3.5"
                                    aria-hidden
                                />
                            </a>
                        </Button>
                        <Button variant="ghost" size="sm" onClick={onDismiss}>
                            {__("Not interested")}
                        </Button>
                    </div>
                </div>

                <button
                    type="button"
                    onClick={onDismiss}
                    aria-label={__("Dismiss this suggestion")}
                    className="fs:absolute fs:right-2 fs:top-2 fs:flex fs:h-8 fs:w-8 fs:cursor-pointer fs:items-center fs:justify-center fs:rounded-md fs:text-slate-400 fs:transition-colors fs:hover:bg-white fs:hover:text-slate-700 fs:focus-visible:outline-none fs:focus-visible:ring-2 fs:focus-visible:ring-brand-500"
                >
                    <X className="fs:h-4 fs:w-4" aria-hidden />
                </button>
            </div>
        </div>
    );
}
