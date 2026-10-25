// Admissions / enrollment seed data
export const ADMISSIONS = [
  { id: "a1", name: "Admission Application – Session 2027-28", status: "open", posted: "01-11-2026", deadline: "30-11-2026", formLink: "/admission", allotted: "0" },
  { id: "a2", name: "Admission Test & Session 2026-27 Results", status: "closed", posted: "13-04-2026", deadline: "24-04-2026", formLink: null, allotted: "18"} ,
  { id: "a3", name: "New Admission Intake 2026-27", status: "closed", posted: "01-04-2026", deadline: "30-04-2026", formLink: null, allotted: "42" },
  { id: "a4", name: "Pre-Primary Admission (LKG–II) 2027-28", status: "open", posted: "02-11-2026", deadline: "15-12-2026", formLink: "/admission", allotted: "0" },
];

export const FEE_STRUCTURE = {
  primary: { science: 300, commerce: 250, humanities: 250, total: 800 },
  secondary: { science: 380, commerce: 340, humanities: 340, total: 1060 },
  senior: { science: 460, commerce: 420, humanities: 420, total: 1300 },
};
