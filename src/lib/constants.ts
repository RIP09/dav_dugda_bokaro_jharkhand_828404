// Authentication defaults
export const AUTH = {
  baseUrl: "/auth",
  loginRoute: "/admin/login",
  signupRoute: "/signup",
  homeRoute: "/",
  dashboardRoute: "/dashboard",
  adminRoute: "/admin",
  // Convex deployment
  convexUrl: "https://dav-dugda-sms.convex.cloud",
};

// DAV Dugda school identity
export const SCHOOL = {
  name: "DAV Public School Dugda",
  short: "DAV Dugda",
  address: "PO Dugda, District Bokaro, Jharkhand 828404",
  phone: "0326 742227, 742312",
  email: "davdugda.bokaro@gmail.com",
  website: "https://davdugda.org",
  founded: "2000",
  board: "CBSE",
  principal: "The Principal",
  description:
    "A prestigious CBSE-affiliated institution run by the DAV College Trust and Management Society, committed to shaping young minds through value-based education.",
};

// House system
export const HOUSES = [
  { id: "sita", name: "Sita House", colour: "#e8c9a0", mascot: "Sita Devi" },
  { id: "ram", name: "Ram House", colour: "#1a3a6b", mascot: "Lord Rama" },
  { id: "hanuman", name: "Hanuman House", colour: "#7a1f2b", mascot: "Lord Hanuman" },
  { id: "laxman", name: "Laxman House", colour: "#2e7d4f", mascot: "Laxman Ji" },
];

// Dress code themes (Summer / Winter)
export const DRESS = {
  summer: {
    boys: "White half-sleeve shirt, fawn (light-brown) checked trousers, black shoes, white socks.",
    girls: "White half-sleeve shirt, fawn checked skirt, black shoes, white socks.",
    note: "White shirt with fawn/gold checked borders; kept neatly ironed.",
  },
  winter: {
    boys: "White full-sleeve shirt, full light-grey or fawn trousers, navy blazer, maroon/neutral tie.",
    girls: "White full-sleeve blouse, fawn skirt, navy cardigan, white dupatta.",
    note: "On P.T. days: plain white uniform for all.",
  },
};

// Page route map per role
export const ROLE_PAGES: Record<string, string[]> = {
  admin: ["/dashboard", "/admin/dashboard", "/admin/staff", "/admin/students", "/admin/fees", "/admin/attendance", "/admin/exams", "/admin/notice", "/admin/settings"],
  staff: ["/dashboard", "/staff/subjects", "/staff/attendance", "/staff/grades", "/staff/notice"],
  teacher: ["/dashboard", "/teacher/classes", "/teacher/attendance", "/teacher/grades", "/teacher/messages"],
  parent: ["/dashboard", "/parent/children", "/parent/attendance", "/parent/grades", "/parent/fees"],
  student: ["/dashboard", "/student/subjects", "/student/attendance", "/student/grades", "/student/timetable"],
  public: ["/", "/about", "/admission", "/notice", "/sports", "/contact"],
};
