// Collects every module area (one folder per area, same names on all platforms).
// Each area's index.js declares: keys (API module keys it owns), screens (custom screen id → component), extensions.
import dashboard from './dashboard';
import students from './students';
import parents from './parents';
import teachers from './teachers';
import staff from './staff';
import admissions from './admissions';
import academics from './academics';
import attendance from './attendance';
import timetable from './timetable';
import homework from './homework';
import exams from './exams';
import certificates from './certificates';
import fees from './fees';
import accounts from './accounts';
import payroll from './payroll';
import inventory from './inventory';
import hostel from './hostel';
import transport from './transport';
import library from './library';
import frontOffice from './front_office';
import communication from './communication';
import reports from './reports';
import settings from './settings';
import profile from './profile';
import leave from './leave';
import audit from './audit';

export const areas = [dashboard, students, parents, teachers, staff, admissions, academics, attendance, timetable, homework, exams,
  certificates, fees, accounts, payroll, inventory, hostel, transport, library, frontOffice, communication, reports, settings, profile, leave, audit];

export const screens = Object.assign({}, ...areas.map(a => a.screens || {}));
export const extensions = Object.assign({}, ...areas.map(a => a.extensions || {}));
export const areaOfModule = Object.fromEntries(areas.flatMap(a => (a.keys || []).map(k => [k, a.name])));

/** Icon for a module/group when the server sends none. */
export const groupIcons = {
  Home: 'dashboard', Dashboard: 'dashboard', Students: 'school', Parents: 'family_restroom', Teachers: 'co_present', Staff: 'badge',
  Admissions: 'person_add', Academic: 'menu_book', Academics: 'menu_book', Attendance: 'fact_check', Exams: 'quiz', Fees: 'payments',
  Accounts: 'account_balance', Payroll: 'request_quote', Library: 'local_library', Transport: 'directions_bus', Hostel: 'hotel',
  Inventory: 'inventory_2', 'Front Office': 'desk', Communication: 'campaign', Certificates: 'workspace_premium', Reports: 'insights',
  Settings: 'settings', System: 'settings', Account: 'person', People: 'groups', Homework: 'assignment', Timetable: 'calendar_view_week',
};
