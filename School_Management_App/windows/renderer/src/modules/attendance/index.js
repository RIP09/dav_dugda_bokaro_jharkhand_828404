// Attendance module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the attendance-specific screens, buttons and tabs.

export default {
  name: 'attendance',
  title: 'Attendance',
  keys: ["student_attendance"],
  screens: { attendance_take: () => import('./TakeAttendance.vue'), attendance_report: () => import('./AttendanceReport.vue'), my_attendance: () => import('./MyAttendance.vue') },
  extensions: {},
};
