import { CalendarDays, Mail, Send } from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/cn";
import { __, sprintf } from "@/lib/i18n";
import { useUiStore } from "@/lib/store";
import { Chart, Legend, num, StatCard } from "@/features/reports/reportUi";
import {
  type ReportData,
  useReports,
  useSendDigest,
} from "@/features/reports/useReports";
import { ROW_DIVIDER, SettingRow, ToggleRow } from "../SettingRow";
import { mailerLabel, type TabProps } from "../types";

const RANGES = [7, 30, 90];

function ReportBody({ data }: { data: ReportData }) {
  const t = data.totals;
  return (
    <div className="fs:space-y-5">
      <div className="fs:grid fs:grid-cols-2 fs:gap-3 fs:sm:grid-cols-3 fs:lg:grid-cols-6">
        <StatCard label={__("Sent")} value={num(t.sent)} />
        <StatCard label={__("Failed")} value={num(t.failed)} />
        <StatCard label={__("Opens")} value={num(t.opens)} />
        <StatCard label={__("Clicks")} value={num(t.clicks)} />
        <StatCard label={__("Open rate")} value={`${t.open_rate}%`} />
        <StatCard label={__("Click rate")} value={`${t.click_rate}%`} />
      </div>

      <div className="fs:rounded-lg fs:border fs:border-slate-200 fs:bg-white fs:p-4">
        <div className="fs:mb-2 fs:flex fs:items-center fs:justify-between">
          <span className="fs:text-sm fs:font-medium fs:text-slate-700">
            {__("Daily activity")}
          </span>
          <Legend />
        </div>
        <Chart series={data.series} />
      </div>

      <div className="fs:grid fs:gap-4 fs:md:grid-cols-2">
        <div className="fs:rounded-lg fs:border fs:border-slate-200 fs:bg-white fs:p-4">
          <h3 className="fs:mb-2 fs:text-sm fs:font-semibold fs:text-slate-800">
            {__("By mailer")}
          </h3>
          {data.mailers.length === 0 ? (
            <p className="fs:text-xs fs:text-slate-400">
              {__("No sends in this period.")}
            </p>
          ) : (
            <ul className="fs:space-y-1">
              {data.mailers.map((m) => (
                <li
                  key={m.mailer}
                  className="fs:flex fs:justify-between fs:text-sm"
                >
                  <span className="fs:text-slate-600">
                    {mailerLabel(m.mailer)}
                  </span>
                  <span className="fs:font-medium fs:text-slate-800">
                    {num(m.count)}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </div>
        <div className="fs:rounded-lg fs:border fs:border-slate-200 fs:bg-white fs:p-4">
          <h3 className="fs:mb-2 fs:text-sm fs:font-semibold fs:text-slate-800">
            {__("Top links")}
          </h3>
          {data.top_links.length === 0 ? (
            <p className="fs:text-xs fs:text-slate-400">
              {__("No clicks in this period.")}
            </p>
          ) : (
            <ul className="fs:space-y-1">
              {data.top_links.map((l) => (
                <li
                  key={l.url}
                  className="fs:flex fs:justify-between fs:gap-3 fs:text-sm"
                >
                  <span className="fs:truncate fs:text-slate-600">{l.url}</span>
                  <span className="fs:shrink-0 fs:font-medium fs:text-slate-800">
                    {num(l.clicks)}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}

export function ReportsTab({ form, setField }: TabProps) {
  const [days, setDays] = useState(30);
  const report = useReports(days);
  const sendDigest = useSendDigest();
  const showToast = useUiStore((s) => s.showToast);

  const onSendTest = () => {
    sendDigest.mutate(days, {
      onSuccess: (res) =>
        res.sent
          ? showToast(
              sprintf(__("Test digest sent to %s."), res.recipients.join(", ")),
            )
          : showToast(__("No recipients configured."), "error"),
      onError: () => showToast(__("Could not send digest."), "error"),
    });
  };

  return (
    <div>
      <div className="fs:p-5">
        <div className="fs:mb-4 fs:flex fs:items-center fs:gap-2">
          <CalendarDays
            className="fs:h-4 fs:w-4 fs:text-slate-400"
            aria-hidden
          />
          {RANGES.map((r) => (
            <button
              key={r}
              type="button"
              onClick={() => setDays(r)}
              className={cn(
                "fs:rounded-md fs:px-3 fs:py-1 fs:text-xs fs:font-medium fs:transition-colors",
                days === r
                  ? "fs:bg-brand-500 fs:text-white"
                  : "fs:bg-slate-100 fs:text-slate-600 fs:hover:bg-slate-200",
              )}
            >
              {sprintf(__("%d days"), r)}
            </button>
          ))}
        </div>

        {report.isLoading && (
          <p className="fs:text-sm fs:text-slate-500">
            {__("Loading report…")}
          </p>
        )}
        {report.isError && (
          <p className="fs:rounded-md fs:bg-red-50 fs:p-3 fs:text-sm fs:text-red-700">
            {__("Failed to load report.")}
          </p>
        )}
        {report.data && <ReportBody data={report.data} />}
      </div>

      <div className="fs:border-t fs:border-slate-100">
        <div className="fs:bg-slate-50/60 fs:px-5 fs:py-2 fs:text-xs fs:font-semibold fs:uppercase fs:tracking-wide fs:text-slate-500">
          {__("Scheduled digests")}
        </div>
        <div className={ROW_DIVIDER}>
          <ToggleRow
            icon={CalendarDays}
            title={__("Weekly report")}
            description={__("Email a summary once a week.")}
            checked={form.enable_weekly_report}
            onChange={(v) => setField("enable_weekly_report", v)}
          />
          <ToggleRow
            icon={CalendarDays}
            title={__("Monthly report")}
            description={__("Email a summary once a month.")}
            checked={form.enable_monthly_report}
            onChange={(v) => setField("enable_monthly_report", v)}
          />
          <SettingRow
            icon={Mail}
            title={__("Recipients")}
            description={__(
              "Comma-separated email addresses. Defaults to the site admin.",
            )}
            htmlFor="fs-report-recipients"
          >
            <Input
              id="fs-report-recipients"
              value={form.report_recipients}
              onChange={(e) => setField("report_recipients", e.target.value)}
              placeholder="admin@example.com"
              className="fs:w-72"
            />
          </SettingRow>
          <SettingRow
            icon={Send}
            title={__("Send a test digest")}
            description={__(
              "Send the current period's report to the recipients now.",
            )}
          >
            <Button
              variant="outline"
              size="sm"
              onClick={onSendTest}
              disabled={sendDigest.isPending}
            >
              <Send className="fs:h-4 fs:w-4" aria-hidden />
              {sendDigest.isPending ? __("Sending…") : __("Send now")}
            </Button>
          </SettingRow>
        </div>
      </div>
    </div>
  );
}
