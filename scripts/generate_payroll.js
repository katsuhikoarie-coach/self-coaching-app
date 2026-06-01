// generate_payroll.js — 給与台帳 (Payroll Ledger) for Google Slides / PowerPoint
// A4 Landscape: 11.69" × 8.27"

const pptxgen = require("pptxgenjs");
const path = require("path");

const OUTPUT = path.join(__dirname, "..", "assets", "payroll", "給与台帳.pptx");

// ── Color palette (no # prefix) ──────────────────────────────────────────────
const C = {
  BG:           "FFFFFF",
  HEADER:       "EEEEEE",
  TOTAL:        "E3F2FD",
  TOTAL_TEXT:   "1565C0",
  SECTION_BG:   "546E7A",
  TITLE_BG:     "37474F",
  NET_BG:       "1565C0",
  NET_ITEM:     "BBDEFB",
  NET_VALUE:    "FFFFFF",
  BORDER:       "BDBDBD",
  TEXT:         "212121",
  LABEL:        "424242",
  MUTED:        "9E9E9E",
  LIGHT:        "ECEFF1",
  ANNUAL_NET:   "1565C0",
  ANNUAL_TOTAL: "CFD8DC",
};

const FONT = "Noto Sans JP";
const LEFT  = 0.3;
const WIDTH = 11.09;   // 11.69 - 2 × 0.3

// ── Cell factory helpers ──────────────────────────────────────────────────────
const bdr = { pt: 0.75, color: C.BORDER };

function hCell(text, align = "center", extra = {}) {
  return { text: String(text), options: { fill: { color: C.HEADER }, bold: true, color: C.LABEL, fontSize: 10, fontFace: FONT, align, valign: "middle", ...extra } };
}
function dCell(text, align = "right", extra = {}) {
  return { text: String(text), options: { fill: { color: C.BG }, color: C.TEXT, fontSize: 10, fontFace: FONT, align, valign: "middle", ...extra } };
}
function tCell(text, align = "right", extra = {}) {
  return { text: String(text), options: { fill: { color: C.TOTAL }, bold: true, color: C.TOTAL_TEXT, fontSize: 11, fontFace: FONT, align, valign: "middle", ...extra } };
}
function nCell(text, align = "right", extra = {}) {
  return { text: String(text), options: { fill: { color: C.NET_BG }, bold: true, color: C.NET_VALUE, fontSize: 11, fontFace: FONT, align, valign: "middle", ...extra } };
}

// ── Build presentation ────────────────────────────────────────────────────────
const pres = new pptxgen();
pres.defineLayout({ name: "A4_LANDSCAPE", width: 11.69, height: 8.27 });
pres.layout = "A4_LANDSCAPE";
pres.title  = "給与台帳";

// ═══════════════════════════════════════════════════════════════════════════════
// SLIDE 1 — COVER
// ═══════════════════════════════════════════════════════════════════════════════
(function buildCover() {
  const s = pres.addSlide();
  s.background = { color: C.BG };

  // ── Top banner ──
  s.addShape(pres.shapes.RECTANGLE, { x: 0, y: 0, w: 11.69, h: 1.0, fill: { color: C.TITLE_BG }, line: { color: C.TITLE_BG, width: 0 } });

  // ── Left accent stripe ──
  s.addShape(pres.shapes.RECTANGLE, { x: 0, y: 0, w: 0.22, h: 8.27, fill: { color: C.SECTION_BG }, line: { color: C.SECTION_BG, width: 0 } });

  // ── Bottom footer bar ──
  s.addShape(pres.shapes.RECTANGLE, { x: 0, y: 7.57, w: 11.69, h: 0.7, fill: { color: C.TITLE_BG }, line: { color: C.TITLE_BG, width: 0 } });

  // ── Main title ──
  s.addText("給 与 台 帳", {
    x: 0.5, y: 1.6, w: 10.7, h: 1.6,
    fontSize: 54, fontFace: FONT, bold: true, color: C.TITLE_BG,
    align: "center", valign: "middle",
  });

  // ── English subtitle ──
  s.addText("PAYROLL LEDGER", {
    x: 0.5, y: 3.3, w: 10.7, h: 0.45,
    fontSize: 13, fontFace: "Arial", color: "90A4AE",
    align: "center", valign: "middle", charSpacing: 10,
  });

  // ── Thin divider ──
  s.addShape(pres.shapes.LINE, { x: 2.2, y: 3.85, w: 7.3, h: 0, line: { color: "CFD8DC", width: 1.5 } });

  // ── Input fields ──
  // colW: [labelW, valW, labelW, valW, labelW, valW]  →  must sum to 8.29
  // 3 × 1.3 = 3.9;  remaining = 8.29 - 3.9 = 4.39;  each val = 4.39/3 ≈ 1.46
  const fW = 8.29;
  const fX = (11.69 - fW) / 2;
  const lW = 1.3; const vW = (fW - lW * 3) / 3; // ~1.463
  const fRow = [
    hCell("年　　度", "center"),  dCell("令和　　年度", "center", { color: "BDBDBD", italic: true }),
    hCell("会 社 名", "center"),  dCell("株式会社〇〇", "center", { color: "BDBDBD", italic: true }),
    hCell("担当者名", "center"),  dCell("〇〇　〇〇",   "center", { color: "BDBDBD", italic: true }),
  ];
  s.addTable([fRow], {
    x: fX, y: 4.05, w: fW, h: 0.6,
    colW: [lW, vW, lW, vW, lW, vW],
    border: bdr,
  });

  // ── Footer note ──
  s.addText("この書類は機密情報です。取り扱いにご注意ください。", {
    x: 0.5, y: 7.65, w: 10.7, h: 0.4,
    fontSize: 9, fontFace: FONT, color: "ECEFF1",
    align: "center", valign: "middle",
  });
})();

// ═══════════════════════════════════════════════════════════════════════════════
// HELPER — Individual payroll slide
// ═══════════════════════════════════════════════════════════════════════════════
function addPayrollSlide(d) {
  const s = pres.addSlide();
  s.background = { color: C.BG };
  let y = 0.22;

  // ── Title bar ──────────────────────────────────────────────────────────────
  const TH = 0.42;
  s.addShape(pres.shapes.RECTANGLE, { x: LEFT, y, w: WIDTH, h: TH, fill: { color: C.TITLE_BG }, line: { color: C.TITLE_BG, width: 0 } });
  s.addText("給 与 明 細 書", { x: LEFT + 0.12, y, w: 5.5, h: TH, fontSize: 14, fontFace: FONT, bold: true, color: C.NET_VALUE, align: "left", valign: "middle", margin: 0 });
  s.addText(d.company || "株式会社〇〇", { x: LEFT + 5.6, y, w: 5.37, h: TH, fontSize: 11, fontFace: FONT, color: C.LIGHT, align: "right", valign: "middle", margin: 0 });
  y += TH + 0.05;

  // ── Basic info table ───────────────────────────────────────────────────────
  // 6 cols: label / value / label / value / label / value
  // 3 × 1.30 = 3.90; remaining = 7.19 → each val = 2.40 / 2.40 / 2.39
  const biRow = [
    hCell("氏　　名", "center"), dCell(d.name   || "", "left",   { bold: true, fontSize: 11 }),
    hCell("所　　属", "center"), dCell(d.dept   || "", "left",   { fontSize: 11 }),
    hCell("対象年月", "center"), dCell(d.period || "", "center", { bold: true, fontSize: 11 }),
  ];
  s.addTable([biRow], { x: LEFT, y, w: WIDTH, h: 0.48, colW: [1.3, 2.40, 1.3, 2.40, 1.3, 2.39], border: bdr });
  y += 0.53;

  // ── Attendance section ─────────────────────────────────────────────────────
  const SH = 0.28;
  s.addShape(pres.shapes.RECTANGLE, { x: LEFT, y, w: WIDTH, h: SH, fill: { color: C.SECTION_BG }, line: { color: C.SECTION_BG, width: 0 } });
  s.addText("■ 勤 怠 情 報", { x: LEFT + 0.12, y, w: WIDTH, h: SH, fontSize: 10, fontFace: FONT, bold: true, color: C.NET_VALUE, align: "left", valign: "middle", margin: 0 });
  y += SH;

  const attCW = WIDTH / 5; // 2.218" each
  const attHdr = ["所定労働日数", "出勤日数", "欠勤日数", "有給取得日数", "残業時間"].map(t => hCell(t));
  const attVal = [
    dCell(d.scheduledDays   || "", "center"),
    dCell(d.workedDays      || "", "center"),
    dCell(d.absentDays      || "", "center"),
    dCell(d.paidLeaveDays   || "", "center"),
    dCell(d.overtimeHours   || "", "center"),
  ];
  s.addTable([attHdr, attVal], {
    x: LEFT, y, w: WIDTH, h: 0.9,
    colW: [attCW, attCW, attCW, attCW, attCW],
    border: bdr,
  });
  y += 0.95;

  // ── Income & Deductions (side by side) ────────────────────────────────────
  const GAP  = 0.15;
  const HALF = (WIDTH - GAP) / 2;   // 5.47"
  const incX = LEFT;
  const dedX = LEFT + HALF + GAP;   // 5.92"

  // Section headers
  s.addShape(pres.shapes.RECTANGLE, { x: incX, y, w: HALF, h: SH, fill: { color: C.SECTION_BG }, line: { color: C.SECTION_BG, width: 0 } });
  s.addText("■ 支 給 項 目", { x: incX + 0.12, y, w: HALF, h: SH, fontSize: 10, fontFace: FONT, bold: true, color: C.NET_VALUE, align: "left", valign: "middle", margin: 0 });
  s.addShape(pres.shapes.RECTANGLE, { x: dedX, y, w: HALF, h: SH, fill: { color: C.SECTION_BG }, line: { color: C.SECTION_BG, width: 0 } });
  s.addText("■ 控 除 項 目", { x: dedX + 0.12, y, w: HALF, h: SH, fontSize: 10, fontFace: FONT, bold: true, color: C.NET_VALUE, align: "left", valign: "middle", margin: 0 });
  y += SH;

  // Column widths inside each half-table
  const lW = HALF * 0.55; // 3.009"
  const vW = HALF * 0.45; // 2.461"
  const RH = 0.43;        // row height

  // Income table
  const incItems = [
    ["基本給",   d.basicSalary        || ""],
    ["役職手当", d.positionAllowance  || ""],
    ["残業手当", d.overtimeAllowance  || ""],
    ["通勤手当", d.commuteAllowance   || ""],
    ["その他手当",d.otherAllowance    || ""],
  ];
  const incRows = incItems.map(([label, val]) => [
    hCell(label, "left"),
    dCell(val ? `¥ ${val}` : "", "right"),
  ]);
  incRows.push([tCell("支 給 合 計", "left"), tCell(d.totalIncome ? `¥ ${d.totalIncome}` : "", "right")]);

  s.addTable(incRows, { x: incX, y, w: HALF, colW: [lW, vW], rowH: RH, border: bdr });

  // Deductions table
  const dedItems = [
    ["健康保険",   d.healthIns         || ""],
    ["厚生年金",   d.pension           || ""],
    ["雇用保険",   d.employmentIns     || ""],
    ["所得税",     d.incomeTax         || ""],
    ["住民税",     d.residentTax       || ""],
    ["その他控除", d.otherDed          || ""],
  ];
  const dedRows = dedItems.map(([label, val]) => [
    hCell(label, "left"),
    dCell(val ? `¥ ${val}` : "", "right"),
  ]);
  dedRows.push([tCell("控 除 合 計", "left"), tCell(d.totalDeductions ? `¥ ${d.totalDeductions}` : "", "right")]);

  s.addTable(dedRows, { x: dedX, y, w: HALF, colW: [lW, vW], rowH: RH, border: bdr });

  y += 7 * RH + 0.1;  // 7 rows (5 items + 1 blank + 1 total)... actually 6 items + 1 total on ded side
  // Income: 5+1=6 rows; Deductions: 6+1=7 rows → take the longer (7 rows)
  // Recalculate: already using 7 rows × RH above

  // ── Net pay bar ───────────────────────────────────────────────────────────
  const NPH = 0.55;
  s.addShape(pres.shapes.RECTANGLE, { x: LEFT, y, w: WIDTH, h: NPH, fill: { color: C.NET_BG }, line: { color: C.NET_BG, width: 0 } });

  const incTxt = d.totalIncome      ? `¥ ${d.totalIncome}`      : "¥ ___,___";
  const dedTxt = d.totalDeductions  ? `¥ ${d.totalDeductions}`  : "¥ ___,___";
  const netTxt = d.netPay           ? `¥ ${d.netPay}`           : "¥ ___,___";

  s.addText([
    { text: "差 引 支 給 額　",   options: { fontSize: 12, color: C.NET_ITEM } },
    { text: `支給合計 ${incTxt}`, options: { fontSize: 10, color: C.NET_ITEM } },
    { text: "  −  ",             options: { fontSize: 12, color: C.NET_ITEM } },
    { text: `控除合計 ${dedTxt}`, options: { fontSize: 10, color: C.NET_ITEM } },
    { text: "  ＝  ",             options: { fontSize: 12, color: C.NET_ITEM } },
    { text: netTxt,              options: { fontSize: 22, bold: true, color: C.NET_VALUE } },
  ], {
    x: LEFT, y, w: WIDTH, h: NPH,
    fontFace: FONT, align: "center", valign: "middle", margin: 0,
  });
  y += NPH + 0.08;

  // ── Footer ────────────────────────────────────────────────────────────────
  s.addText("※ この給与明細は機密情報です。取り扱いに十分ご注意ください。", {
    x: LEFT, y, w: WIDTH, h: 0.25,
    fontSize: 8, fontFace: FONT, color: C.MUTED, italic: true,
    align: "left", valign: "middle",
  });
}

// ── Sample employee (Slide 2) ──────────────────────────────────────────────────
addPayrollSlide({
  company:           "株式会社〇〇",
  name:              "山田　太郎",
  dept:              "営業部",
  period:            "2025年4月",
  scheduledDays:     "21日",
  workedDays:        "20日",
  absentDays:        "1日",
  paidLeaveDays:     "1日",
  overtimeHours:     "8.5時間",
  basicSalary:       "300,000",
  positionAllowance: "20,000",
  overtimeAllowance: "15,300",
  commuteAllowance:  "10,000",
  otherAllowance:    "0",
  totalIncome:       "345,300",
  healthIns:         "17,420",
  pension:           "31,110",
  employmentIns:     "1,036",
  incomeTax:         "7,590",
  residentTax:       "12,000",
  otherDed:          "0",
  totalDeductions:   "69,156",
  netPay:            "276,144",
});

// ── Blank template (Slide 3) ───────────────────────────────────────────────────
addPayrollSlide({ company: "株式会社〇〇" });

// ═══════════════════════════════════════════════════════════════════════════════
// FINAL SLIDE — Annual summary (12-month table)
// ═══════════════════════════════════════════════════════════════════════════════
(function buildAnnualSummary() {
  const s = pres.addSlide();
  s.background = { color: C.BG };
  let y = 0.22;

  // ── Title bar ──────────────────────────────────────────────────────────────
  const TH = 0.42;
  s.addShape(pres.shapes.RECTANGLE, { x: LEFT, y, w: WIDTH, h: TH, fill: { color: C.TITLE_BG }, line: { color: C.TITLE_BG, width: 0 } });
  s.addText("年 間 給 与 累 計 表", { x: LEFT + 0.12, y, w: 6, h: TH, fontSize: 14, fontFace: FONT, bold: true, color: C.NET_VALUE, align: "left", valign: "middle", margin: 0 });
  s.addText("氏名：山田　太郎　　対象年度：2025年度", { x: LEFT + 6, y, w: 5, h: TH, fontSize: 10, fontFace: FONT, color: C.LIGHT, align: "right", valign: "middle", margin: 0 });
  y += TH + 0.08;

  // ── Column widths ──────────────────────────────────────────────────────────
  // 14 cols: [labelCol] + 12×[monthCol] + [totalCol]
  // label=0.90, total=0.95, each month=(11.09-0.90-0.95)/12 = 9.24/12 = 0.77
  const lCW = 0.90;
  const tCW = 0.95;
  const mCW = (WIDTH - lCW - tCW) / 12; // 0.77"
  const colW = [lCW, ...Array(12).fill(mCW), tCW];

  const months = ["4月","5月","6月","7月","8月","9月","10月","11月","12月","1月","2月","3月"];

  // Sample data (yen, no commas here — will format below)
  const incomes  = [345300,345300,360600,345300,395300,345300,345300,360600,345300,345300,345300,345300];
  const deducts  = [69156, 69156, 71856, 69156, 75156, 69156, 69156, 71856, 69156, 69156, 69156, 69156];
  const nets     = incomes.map((v, i) => v - deducts[i]);
  const totInc   = incomes.reduce((a, b) => a + b, 0);
  const totDed   = deducts.reduce((a, b) => a + b, 0);
  const totNet   = nets.reduce((a, b) => a + b, 0);

  const fmt = (n) => n.toLocaleString("ja-JP");

  // Header row
  const hdrRow = [
    hCell("月", "center"),
    ...months.map(m => hCell(m, "center")),
    { text: "合　計", options: { fill: { color: C.ANNUAL_TOTAL }, bold: true, color: C.TITLE_BG, fontSize: 10, fontFace: FONT, align: "center", valign: "middle" } },
  ];

  // Income row
  const incRow = [
    hCell("支給合計", "left"),
    ...incomes.map(v => dCell(fmt(v), "right", { fontSize: 9 })),
    tCell(fmt(totInc), "right", { fontSize: 9 }),
  ];

  // Deduction row
  const dedRow = [
    hCell("控除合計", "left"),
    ...deducts.map(v => dCell(fmt(v), "right", { fontSize: 9 })),
    tCell(fmt(totDed), "right", { fontSize: 9 }),
  ];

  // Net pay row (dark blue)
  const netRow = [
    nCell("差引支給額", "left"),
    ...nets.map(v => ({ text: fmt(v), options: { fill: { color: "E3F2FD" }, color: C.NET_BG, bold: true, fontSize: 9, fontFace: FONT, align: "right", valign: "middle" } })),
    nCell(fmt(totNet), "right", { fontSize: 9 }),
  ];

  s.addTable([hdrRow, incRow, dedRow, netRow], {
    x: LEFT, y, w: WIDTH,
    colW,
    rowH: 0.62,
    border: bdr,
  });

  y += 4 * 0.62 + 0.15;

  // ── Summary boxes ──────────────────────────────────────────────────────────
  const boxes = [
    { label: "年間支給合計",  value: fmt(totInc), bg: C.HEADER,  fg: C.TEXT,        vfg: C.TITLE_BG },
    { label: "年間控除合計",  value: fmt(totDed), bg: C.TOTAL,   fg: C.LABEL,       vfg: C.TOTAL_TEXT },
    { label: "年間差引支給額",value: fmt(totNet), bg: C.NET_BG,  fg: "BBDEFB",      vfg: C.NET_VALUE },
  ];
  const bW = WIDTH / 3 - 0.1;
  boxes.forEach((box, i) => {
    const bx = LEFT + i * (bW + 0.15);
    s.addShape(pres.shapes.RECTANGLE, { x: bx, y, w: bW, h: 0.85, fill: { color: box.bg }, line: { color: C.BORDER, width: 0.75 } });
    s.addText(box.label, { x: bx + 0.1, y: y + 0.04, w: bW - 0.2, h: 0.3, fontSize: 10, fontFace: FONT, color: box.fg, align: "center", valign: "middle" });
    s.addText(`¥ ${box.value}`, { x: bx + 0.1, y: y + 0.38, w: bW - 0.2, h: 0.42, fontSize: 18, fontFace: FONT, bold: true, color: box.vfg, align: "center", valign: "middle" });
  });

  y += 1.0;

  // ── Footer ────────────────────────────────────────────────────────────────
  s.addText("※ 金額の単位は円です。サンプルデータのため、実際の給与とは異なります。", {
    x: LEFT, y, w: WIDTH, h: 0.28,
    fontSize: 9, fontFace: FONT, color: C.MUTED, italic: true, align: "left", valign: "middle",
  });
})();

// ── Write file ────────────────────────────────────────────────────────────────
pres.writeFile({ fileName: OUTPUT })
  .then(() => console.log(`✓ Created: ${OUTPUT}`))
  .catch(err => { console.error("Error:", err); process.exit(1); });
