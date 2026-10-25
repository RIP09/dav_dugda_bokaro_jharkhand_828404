import { GraduationCap, Users, Award, Clock, TrendingUp, BarChart3, Settings } from "lucide-react";
import { Link } from "react-router-dom";
import { STAFF } from "@/lib/staff";
import { STUDENTS } from "@/lib/students";

export default function AdminDashboard() {
  const stats = [
    { label: "Total Students", value: STUDENTS.length.toString(), icon: GraduationCap },
    { label: "Total Staff", value: STAFF.length.toString(), icon: Users },
    { label: "Active Classes", value: "18", icon: BarChart3 },
    { label: "Fees Collected", value: "₹8,42,350", icon: TrendingUp },
  ];

  return (
    <div className="max-w-7xl mx-auto">
      <h1 className="text-2xl font-bold text-ink mb-1">Admin Dashboard</h1>
      <p className="text-slate-400 mb-8">Complete control of school data. Edit the website content here without touching code.</p>

      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {stats.map((s) => (
          <div key={s.label} className="card p-5">
            <div className="w-10 h-10 rounded-xl bg-dav-brand/10 flex items-center justify-center mb-3">
              <s.icon size={18} className="text-dav-brand" />
            </div>
            <div className="text-2xl font-bold text-ink">{s.value}</div>
            <div className="text-xs text-slate-400">{s.label}</div>
          </div>
        ))}
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <div className="card p-6">
          <h3 className="font-bold text-ink mb-3">Staff Directory</h3>
          <div className="space-y-2">
            {STAFF.map((s) => (
              <div key={s.id} className="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50">
                <div>
                  <div className="font-semibold text-ink text-sm">{s.name}</div>
                  <div className="text-xs text-slate-400">{s.role}</div>
                </div>
                <span className="text-xs text-slate-400">{s.phone}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="card p-6">
          <h3 className="font-bold text-ink mb-3">Quick Actions</h3>
          <div className="grid sm:grid-cols-2 gap-3">
            {[
              { label: "Add / Edit Student", to: "/admin/students" },
              { label: "Add / Edit Staff", to: "/admin/staff" },
              { label: "Fees & Receipts", to: "/admin/fees" },
              { label: "Exam & Results", to: "/admin/exams" },
              { label: "Attendance Register", to: "/admin/attendance" },
              { label: "Notice & Events", to: "/admin/notice" },
              { label: "Website Editor", to: "/admin/website" },
              { label: "Subject Timetable", to: "/admin/timetable" },
            ].map((a) => (
              <Link key={a.to} to={a.to} className="flex items-center gap-3 p-3 rounded-xl border border-line hover:bg-sand transition group">
                <span className="p-2 rounded-lg bg-dav-brand/10 text-dav-brand group-hover:scale-110 transition">
                  <Award size={16} />
                </span>
                <span className="font-medium text-sm text-ink">{a.label}</span>
              </Link>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
