import { ArrowRight, ChevronRight, RefreshCw } from "lucide-react";
import { type ReactNode } from "react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/cn";
import { __, sprintf } from "@/lib/i18n";
import { useUiStore } from "@/lib/store";
import { getPluginGlobal } from "@/lib/wp";
import {
  HEALTH_HEADLINE,
  type Tone,
  TONE_STYLES,
  healthTone,
  relativeTime,
  statusLabel,
} from "@/features/overview/healthUi";
import {
  type HealthReport,
  type MonitoringData,
  useHealth,
  useMonitoring,
  useRunHealth,
} from "@/features/overview/useOverview";
import { Chart, Legend, num, StatCard } from "@/features/reports/reportUi";
import { type ReportData, useReports } from "@/features/reports/useReports";
import { type LogItem, useLogs } from "@/features/logs/useLogs";
import { categoryLabel } from "@/features/settings/types";

const MONITOR_DAYS = 30;
const REPORT_DAYS = 7;
const RECENT_LIMIT = 6;

/** A framed panel with a heading and an optional top-right action. */
function Panel({
  title,
  action,
  children,
}: {
  title: string;
  action?: ReactNode;
  children: ReactNode;
}) {
  return (
    <section className="fs:rounded-xl fs:border fs:border-slate-200 fs:bg-white fs:p-4 fs:shadow-sm">
      <div className="fs:mb-3 fs:flex fs:items-center fs:justify-between fs:gap-2">
        <h3 className="fs:text-sm fs:font-semibold fs:text-slate-800">
          {title}
        </h3>
        {action}
      </div>
      {children}
    </section>
  );
}

/** A "See everything →" link that jumps to a full settings tab. */
function ViewAll({ section, label }: { section: string; label: string }) {
  const setActive = useUiStore((s) => s.setActiveSection);
  return (
    <button
      type="button"
      onClick={() => setActive(section)}
      className="fs:inline-flex fs:items-center fs:gap-1 fs:text-xs fs:font-medium fs:text-brand-600 fs:transition-colors fs:hover:text-brand-700"
    >
      {label}
      <ArrowRight className="fs:h-3.5 fs:w-3.5" aria-hidden />
    </button>
  );
}

function Empty({ children }: { children: ReactNode }) {
  return (
    <p className="fs:rounded-lg fs:border fs:border-dashed fs:border-slate-200 fs:px-4 fs:py-6 fs:text-center fs:text-sm fs:text-slate-400">
      {children}
    </p>
  );
}

/* --- Hero: overall health --------------------------------------------- */

function HeroHealth({ report }: { report: HealthReport }) {
  const tone = healthTone(report.status);
  // Only pass/warn/error checks carry a verdict; na/not_checked don't count.
  const scored = report.checks.filter((c) =>
    ["pass", "warn", "error"].includes(c.status),
  );
  const passed = scored.filter((c) => c.status === "pass").length;

  return (
    <div className="fs:space-y-2">
      <div className="fs:flex fs:flex-wrap fs:items-center fs:gap-3">
        <span
          className={cn(
            "fs:inline-flex fs:items-center fs:gap-1.5 fs:rounded-full fs:px-3 fs:py-1 fs:text-sm fs:font-medium fs:ring-1",
            TONE_STYLES[tone].chip,
          )}
        >
          {(() => {
            const Icon = TONE_STYLES[tone].icon;
            return <Icon className="fs:h-4 fs:w-4" aria-hidden />;
          })()}
          {statusLabel(report.status)}
        </span>
        <p className={cn("fs:text-sm fs:font-medium", TONE_STYLES[tone].text)}>
          {HEALTH_HEADLINE[tone]}
        </p>
      </div>
      <p className="fs:text-xs fs:text-slate-500">
        {scored.length > 0
          ? sprintf(
              __("%1$d of %2$d checks passed · last checked %3$s"),
              passed,
              scored.length,
              relativeTime(report.generated_at),
            )
          : sprintf(__("Last checked %s"), relativeTime(report.generated_at))}
      </p>
    </div>
  );
}

/* --- Stat cards (7-day) ----------------------------------------------- */

function StatGrid({ data }: { data: ReportData }) {
  const t = data.totals;
  return (
    <div className="fs:grid fs:grid-cols-2 fs:gap-3 fs:sm:grid-cols-3 fs:lg:grid-cols-6">
      <StatCard label={__("Sent")} value={num(t.sent)} />
      <StatCard label={__("Failed")} value={num(t.failed)} />
      <StatCard label={__("Opens")} value={num(t.opens)} />
      <StatCard label={__("Clicks")} value={num(t.clicks)} />
      <StatCard label={__("Open rate")} value={`${t.open_rate}%`} />
      <StatCard label={__("Click rate")} value={`${t.click_rate}%`} />
    </div>
  );
}

/* --- Health checks summary -------------------------------------------- */

function rank(status: string): number {
  switch (status) {
    case "error":
      return 3;
    case "warn":
      return 2;
    case "pass":
      return 1;
    default:
      return 0;
  }
}

function HealthChecks({ report }: { report: HealthReport }) {
  // Surface the checks that need attention first; cap the list so the panel
  // stays a summary, not a duplicate of the Overview.
  const checks = [...report.checks]
    .sort((a, b) => rank(b.status) - rank(a.status))
    .slice(0, 5);

  return (
    <ul className="fs:space-y-2">
      {checks.map((c) => {
        const t = TONE_STYLES[healthTone(c.status)];
        const Icon = t.icon;
        return (
          <li
            key={`${c.group}:${c.id}`}
            className="fs:flex fs:items-center fs:gap-2.5"
          >
            <Icon
              className={cn("fs:h-4 fs:w-4 fs:shrink-0", t.text)}
              aria-hidden
            />
            <span className="fs:min-w-0 fs:flex-1 fs:truncate fs:text-sm fs:text-slate-700">
              {c.label}
            </span>
            <span
              className={cn(
                "fs:shrink-0 fs:rounded fs:px-1.5 fs:py-0.5 fs:text-xs fs:font-medium fs:ring-1",
                t.chip,
              )}
            >
              {statusLabel(c.status)}
            </span>
          </li>
        );
      })}
    </ul>
  );
}

/* --- Recent activity -------------------------------------------------- */

function logTone(status: number): Tone {
  if (status === 1) return "pass";
  if (status === 2) return "warn";
  return "error";
}

function logStatusLabel(status: number): string {
  if (status === 1) return __("Sent");
  if (status === 2) return __("Pending");
  return __("Failed");
}

function RecentActivity({ items }: { items: LogItem[] }) {
  if (items.length === 0) {
    return <Empty>{__("No emails logged yet.")}</Empty>;
  }
  return (
    <ul className="fs:divide-y fs:divide-slate-100">
      {items.map((log) => {
        const t = TONE_STYLES[logTone(log.status)];
        return (
          <li key={log.id} className="fs:flex fs:items-center fs:gap-3 fs:py-2">
            <span
              className={cn(
                "fs:shrink-0 fs:rounded-full fs:px-2 fs:py-0.5 fs:text-xs fs:font-medium fs:ring-1",
                t.chip,
              )}
            >
              {logStatusLabel(log.status)}
            </span>
            <span className="fs:min-w-0 fs:flex-1 fs:truncate fs:text-sm fs:text-slate-800">
              {log.subject || __("(no subject)")}
            </span>
            <span className="fs:shrink-0 fs:whitespace-nowrap fs:text-xs fs:text-slate-400">
              {log.date_time}
            </span>
          </li>
        );
      })}
    </ul>
  );
}

/* --- Failure causes --------------------------------------------------- */

function FailureCauses({ data }: { data: MonitoringData }) {
  const setActive = useUiStore((s) => s.setActiveSection);
  const setCategory = useUiStore((s) => s.setLogCategory);
  const cats = data.categories;
  const max = Math.max(1, ...cats.map((c) => c.count));

  if (cats.length === 0) {
    return <Empty>{__("No failures in the last 30 days.")}</Empty>;
  }

  const openLogs = (category: string) => {
    setCategory(category);
    setActive("logs");
  };

  return (
    <div className="fs:space-y-2">
      {cats.slice(0, 5).map((c) => (
        <button
          key={c.category}
          type="button"
          onClick={() => openLogs(c.category)}
          className="fs:group fs:flex fs:w-full fs:items-center fs:gap-3 fs:rounded-lg fs:px-2 fs:py-1.5 fs:text-left fs:transition-colors fs:hover:bg-slate-50"
        >
          <span className="fs:w-32 fs:shrink-0 fs:truncate fs:text-sm fs:text-slate-700">
            {categoryLabel(c.category)}
          </span>
          <span className="fs:relative fs:h-2 fs:flex-1 fs:overflow-hidden fs:rounded-full fs:bg-slate-100">
            <span
              className="fs:absolute fs:inset-y-0 fs:left-0 fs:rounded-full fs:bg-brand-500"
              style={{ width: `${(c.count / max) * 100}%` }}
            />
          </span>
          <span className="fs:w-8 fs:shrink-0 fs:text-right fs:text-sm fs:font-medium fs:text-slate-800">
            {c.count}
          </span>
          <ChevronRight
            className="fs:h-4 fs:w-4 fs:shrink-0 fs:text-slate-300 fs:group-hover:text-brand-600"
            aria-hidden
          />
        </button>
      ))}
    </div>
  );
}

/* --- Page ------------------------------------------------------------- */

export function DashboardTab() {
  const health = useHealth();
  const runHealth = useRunHealth();
  const monitoring = useMonitoring(MONITOR_DAYS);
  const reports = useReports(REPORT_DAYS);
  const recent = useLogs({
    page: 1,
    per_page: RECENT_LIMIT,
    search: "",
    status: "",
    mailer: "",
    error_category: "",
  });
  const showToast = useUiStore((s) => s.showToast);
  const { canManageSettings } = getPluginGlobal();

  const onRun = () => {
    runHealth.mutate(undefined, {
      onSuccess: () => showToast(__("Health checks refreshed.")),
      onError: () => showToast(__("Could not run the checks."), "error"),
    });
  };

  return (
    <div className="fs:space-y-5 fs:p-5">
      {/* Hero: is email working right now? */}
      <section className="fs:rounded-xl fs:border fs:border-slate-200 fs:bg-white fs:p-5 fs:shadow-sm">
        <div className="fs:flex fs:flex-wrap fs:items-start fs:justify-between fs:gap-4">
          {health.isLoading && (
            <p className="fs:text-sm fs:text-slate-500">{__("Loading…")}</p>
          )}
          {health.isError && (
            <p className="fs:text-sm fs:text-red-700">
              {__("Failed to load the health report.")}
            </p>
          )}
          {health.data && <HeroHealth report={health.data} />}
          <Button
            variant="outline"
            size="sm"
            onClick={onRun}
            disabled={runHealth.isPending || !canManageSettings}
          >
            <RefreshCw
              className={cn(
                "fs:h-4 fs:w-4",
                runHealth.isPending && "fs:animate-spin",
              )}
              aria-hidden
            />
            {runHealth.isPending ? __("Checking…") : __("Run checks")}
          </Button>
        </div>
      </section>

      {/* 7-day figures */}
      {reports.data && <StatGrid data={reports.data} />}

      {/* Activity + health checks */}
      <div className="fs:grid fs:gap-4 fs:lg:grid-cols-3">
        <div className="fs:lg:col-span-2">
          <Panel
            title={sprintf(__("Activity · last %d days"), REPORT_DAYS)}
            action={<Legend />}
          >
            {reports.isLoading && (
              <p className="fs:text-sm fs:text-slate-500">{__("Loading…")}</p>
            )}
            {reports.data && <Chart series={reports.data.series} />}
          </Panel>
        </div>
        <div className="fs:lg:col-span-1">
          <Panel
            title={__("Setup health")}
            action={<ViewAll section="overview" label={__("Details")} />}
          >
            {health.data && <HealthChecks report={health.data} />}
          </Panel>
        </div>
      </div>

      {/* Recent activity + failure causes */}
      <div className="fs:grid fs:gap-4 fs:lg:grid-cols-3">
        <div className="fs:lg:col-span-2">
          <Panel
            title={__("Recent activity")}
            action={<ViewAll section="logs" label={__("All logs")} />}
          >
            {recent.isLoading && (
              <p className="fs:text-sm fs:text-slate-500">{__("Loading…")}</p>
            )}
            {recent.data && <RecentActivity items={recent.data.items} />}
          </Panel>
        </div>
        <div className="fs:lg:col-span-1">
          <Panel
            title={__("Why sends fail")}
            action={<ViewAll section="logs" label={__("All logs")} />}
          >
            {monitoring.data && <FailureCauses data={monitoring.data} />}
          </Panel>
        </div>
      </div>
    </div>
  );
}
