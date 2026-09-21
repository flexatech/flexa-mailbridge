import { __ } from "@/lib/i18n";
import { type ReportPoint } from "./useReports";

/** Shared report visuals reused by the Reports tab and the Dashboard. */
export const OPENS_COLOR = "#10b981";
export const CLICKS_COLOR = "#f59e0b";

export function num(n: number): string {
  return n.toLocaleString();
}

export function StatCard({ label, value }: { label: string; value: string }) {
  return (
    <div className="fs:rounded-lg fs:border fs:border-slate-200 fs:bg-white fs:px-4 fs:py-3">
      <div className="fs:text-xl fs:font-semibold fs:text-slate-900">
        {value}
      </div>
      <div className="fs:text-xs fs:uppercase fs:tracking-wide fs:text-slate-500">
        {label}
      </div>
    </div>
  );
}

export function Chart({ series }: { series: ReportPoint[] }) {
  const W = 640;
  const H = 180;
  const pad = { t: 12, r: 8, b: 4, l: 8 };
  const chartW = W - pad.l - pad.r;
  const chartH = H - pad.t - pad.b;
  const n = Math.max(1, series.length);
  const max = Math.max(
    1,
    ...series.map((p) => Math.max(p.sent, p.opens, p.clicks)),
  );
  const bw = chartW / n;
  const baseline = pad.t + chartH;
  const y = (v: number) => pad.t + chartH - (v / max) * chartH;
  const cx = (i: number) => pad.l + i * bw + bw / 2;

  const line = (key: "opens" | "clicks") =>
    series.map((p, i) => `${cx(i)},${y(p[key])}`).join(" ");

  return (
    <svg
      viewBox={`0 0 ${W} ${H}`}
      className="fs:h-44 fs:w-full"
      preserveAspectRatio="none"
      role="img"
      aria-label={__("Daily email activity")}
    >
      <line
        x1={pad.l}
        y1={baseline}
        x2={W - pad.r}
        y2={baseline}
        stroke="#e2e8f0"
        strokeWidth={1}
      />
      {series.map((p, i) => {
        const h = (p.sent / max) * chartH;
        return (
          <rect
            key={p.date}
            x={pad.l + i * bw + bw * 0.2}
            y={baseline - h}
            width={Math.max(1, bw * 0.6)}
            height={h}
            rx={1}
            fill="var(--fs-color-brand-500)"
            opacity={0.85}
          >
            <title>{`${p.date}: ${p.sent} sent`}</title>
          </rect>
        );
      })}
      {series.length > 1 && (
        <>
          <polyline
            points={line("opens")}
            fill="none"
            stroke={OPENS_COLOR}
            strokeWidth={1.75}
          />
          <polyline
            points={line("clicks")}
            fill="none"
            stroke={CLICKS_COLOR}
            strokeWidth={1.75}
          />
        </>
      )}
    </svg>
  );
}

export function Legend() {
  const item = (color: string, label: string) => (
    <span className="fs:inline-flex fs:items-center fs:gap-1.5">
      <span
        className="fs:inline-block fs:h-2.5 fs:w-2.5 fs:rounded-sm"
        style={{ backgroundColor: color }}
      />
      <span className="fs:text-xs fs:text-slate-500">{label}</span>
    </span>
  );
  return (
    <div className="fs:flex fs:items-center fs:gap-4">
      {item("var(--fs-color-brand-500)", __("Sent"))}
      {item(OPENS_COLOR, __("Opens"))}
      {item(CLICKS_COLOR, __("Clicks"))}
    </div>
  );
}
