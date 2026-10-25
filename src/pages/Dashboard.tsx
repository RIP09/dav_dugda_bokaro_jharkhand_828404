import { GraduationCap, Users, Clock, Award, FileCheck, TrendingUp, Settings, BarChart3 } from "lucide-react";
import { Link } from "react-router-dom";
import { SCHOOL } from "@/lib/constants";
import { STAFF } from "@/lib/staff";

const stats = [
  { label: "Students", value: "1,247", icon: GraduationCap, color: "bg-dav-brand" },
  { label: "Teachers", value: "48", icon: Users, color: "bg-amber-100 text-amber-600" },
  { label: "Classes", value: "18", icon: BarChart3, color: "bg-dav-green text-white" },
  { label: "Attendance Today", value: "96.4%", icon: Clock, color: "bg-dav-maroon text-white" },
];

const quickLinks = [
  { icon: FileCheck, label: "Exams & Results", to: "/exams" },
  { icon: TrendingUp, label: "Fees & Accounts", to: "/fees" },
  { icon: Clock, label: "Attendance", to: "/attendance" },
  { icon: Award, label: "Events & Notices", to: "/notice" },
  { icon: Settings, label: "Settings", to: "/settings" },
];

export default function Dashboard() {
  return (
    <div className="max-w-7xl mx-auto">
      <div className="text-sm text-slate-400 mb-1">Dashboard</div>
      <h1 className="text-2xl font-bold text-ink mb-1">Welcome, {import.meta.env.VITE_USER_NAME || "Admin"}</h1>
      <p className="text-slate-400 mb-8">{SCHOOL.name} &mdash; all your school data, in one place.</p>

      {/* Stats */}
      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {stats.map((s) => (
          <div key={s.label} className="card p-5">
            <div className={`w-10 h-10 rounded-xl ${s.color} flex items-center justify-center mb-3`}>
              <s.icon size={18} />
            </div>
            <div className="text-2xl font-bold text-ink">{s.value}</div>
            <div className="text-xs text-slate-400">{s.label}</div>
          </div>
        ))}
      </div>

      {/* Quick links */}
      <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        {quickLinks.map((l) => (
          <Link key={l.to} to={l.to} className="card p-5 flex items-center gap-3 group">
            <l.icon size={18} className="text-dav-brand group-hover:scale-110 transition" />
            <div>
              <div className="font-semibold text-ink text-sm">{l.label}</div>
            </div>
          </Link>
        ))}
      </div>

      {/* Staff directory preview */}
      <div className="card p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-bold text-ink">Staff Directory</h3>
          <Link to="/staff" className="text-sm text-dav-brand font-medium">View all</Link>
        </div>
        <div className="grid sm:grid-cols-2 gap-3">
          {STAFF.slice(0, 8).map((s) => (
            <div key={s.id} className="flex items-center gap-3 p-3 rounded-xl bg-slate-50">
              <div className="w-9 h-9 rounded-full bg-dav-brand/10 flex items-center justify-center text-sm font-bold text-dav-brand">
                {s.name.split(" ").map((n) => n[0]).join("")}
              </div>
              <div className="text-sm text-ink">
                <div className="font-semibold">{s.name}</div>
                <div className="text-xs text-slate-400">{s.role}</div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
