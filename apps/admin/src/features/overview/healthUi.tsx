import {
  AlertTriangle,
  CheckCircle2,
  CircleHelp,
  XCircle,
  type LucideIcon,
} from "lucide-react";
import { cn } from "@/lib/cn";
import { __ } from "@/lib/i18n";
import { type HealthSignal } from "./useOverview";

/** Shared visual language for health/deliverability status across the
 *  Overview and Dashboard. Kept in one place so a status colour or icon only
 *  ever needs changing once. */
export type Tone = "pass" | "warn" | "error" | "neutral";

export const TONE_STYLES: Record<
  Tone,
  { icon: LucideIcon; chip: string; text: string; dot: string }
> = {
  pass: {
    icon: CheckCircle2,
    chip: "fs:bg-emerald-50 fs:text-emerald-700 fs:ring-emerald-200",
    text: "fs:text-emerald-700",
    dot: "fs:bg-emerald-500",
  },
  warn: {
    icon: AlertTriangle,
    chip: "fs:bg-amber-50 fs:text-amber-700 fs:ring-amber-200",
    text: "fs:text-amber-700",
    dot: "fs:bg-amber-500",
  },
  error: {
    icon: XCircle,
    chip: "fs:bg-red-50 fs:text-red-700 fs:ring-red-200",
    text: "fs:text-red-700",
    dot: "fs:bg-red-500",
  },
  neutral: {
    icon: CircleHelp,
    chip: "fs:bg-slate-100 fs:text-slate-600 fs:ring-slate-200",
    text: "fs:text-slate-600",
    dot: "fs:bg-slate-400",
  },
};

export function healthTone(status: string): Tone {
  if (status === "pass") return "pass";
  if (status === "warn") return "warn";
  if (status === "error") return "error";
  return "neutral";
}

export function signalTone(signal: HealthSignal): Tone {
  if (signal === "healthy") return "pass";
  if (signal === "degraded") return "warn";
  if (signal === "failing") return "error";
  return "neutral";
}

export function statusLabel(status: string): string {
  switch (status) {
    case "pass":
      return __("Passed");
    case "warn":
      return __("Needs attention");
    case "error":
      return __("Problem found");
    case "na":
      return __("Not applicable");
    default:
      return __("Not checked");
  }
}

export function signalLabel(signal: HealthSignal): string {
  switch (signal) {
    case "healthy":
      return __("Healthy");
    case "degraded":
      return __("Degraded");
    case "failing":
      return __("Failing");
    default:
      return __("Not enough data");
  }
}

/** Headline shown next to the overall status pill. */
export const HEALTH_HEADLINE: Record<Tone, string> = {
  pass: __("Your setup looks ready to send email."),
  warn: __("Email can still send, but some things should be improved."),
  error: __("Something is likely to stop or degrade your email delivery."),
  neutral: __("Run the checks to see whether your setup is ready."),
};

export function StatusPill({ status }: { status: string }) {
  const tone = TONE_STYLES[healthTone(status)];
  const Icon = tone.icon;
  return (
    <span
      className={cn(
        "fs:inline-flex fs:items-center fs:gap-1.5 fs:rounded-full fs:px-3 fs:py-1 fs:text-sm fs:font-medium fs:ring-1",
        tone.chip,
      )}
    >
      <Icon className="fs:h-4 fs:w-4" aria-hidden />
      {statusLabel(status)}
    </span>
  );
}

/** A single figure with a small uppercase label. `tone` colours the number. */
export function Stat({
  label,
  value,
  tone = "neutral",
}: {
  label: string;
  value: string;
  tone?: Tone;
}) {
  return (
    <div className="fs:rounded-lg fs:border fs:border-slate-200 fs:px-4 fs:py-3">
      <div
        className={cn(
          "fs:text-xl fs:font-semibold",
          tone === "neutral" ? "fs:text-slate-900" : TONE_STYLES[tone].text,
        )}
      >
        {value}
      </div>
      <div className="fs:text-xs fs:uppercase fs:tracking-wide fs:text-slate-500">
        {label}
      </div>
    </div>
  );
}

export function relativeTime(unix: number): string {
  if (unix <= 0) {
    return __("never");
  }
  return new Date(unix * 1000).toLocaleString();
}
