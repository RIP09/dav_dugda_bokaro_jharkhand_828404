import { Link } from "react-router-dom";
import { FileCheck, Clock, Calendar, ChevronRight, Bell } from "lucide-react";

export default function PublicNotice() {
  const notices = [
    { id: "n1", title: "Annual Day Celebrations 2026-27", date: "02-12-2026", type: "Event", desc: "Invitation to parents for the annual day, prize distribution and cultural performances." },
    { id: "n2", title: "Half-Yearly Examination Schedule", date: "15-10-2026", type: "Academic", desc: "Timings and dates for Half-Yearly exams. Classes VI–X. Students to report by 8:15 AM." },
    { id: "n3", title: "Annual Magazine Submission", date: "01-11-2026", type: "Academic", desc: "Students to submit articles, poems and drawings by 30-11-2026 to their class teacher." },
    { id: "n4", title: "PT & Sports Day Preparation", date: "15-10-2026", type: "Sports", desc: "Winter uniform to be worn on P.T. days. Games to be brought accordingly." },
    { id: "n5", title: "Mother Tongue Day", date: "21-11-2026", type: "Event", desc: "Special assembly on Mother Tongue Day. Cultural programs across all classes." },
  ];

  return (
    <div className="max-w-7xl mx-auto px-4 md:px-8 py-10 scroll-margin">
      <div className="mb-10">
        <Link to="/" className="text-sm text-dav-brand hover:underline flex items-center gap-1">
          <span className="w-5 h-5 rounded bg-dav-brand flex items-center justify-center text-white text-xs">D</span>
          Home
        </Link>
        <span className="text-slate-300">/</span>
        <span className="text-sm font-semibold text-ink">Noticeboard</span>
      </div>

      <div className="flex items-center gap-2 mb-6">
        <h1 className="text-3xl font-bold text-ink">Noticeboard</h1>
        <span className="text-sm text-slate-400 font-medium">{notices.length} posts</span>
      </div>

      <div className="grid md:grid-cols-2 gap-4">
        {notices.map((n) => (
          <div key={n.id} className="card p-5">
            <div className="flex items-center gap-2 mb-3">
              <span className={`px-2.5 py-1 rounded-full text-xs font-semibold ${
                n.type === "Academic" ? "bg-blue-100 text-blue-700" :
                n.type === "Event" ? "bg-amber-100 text-amber-700" :
                "bg-emerald-100 text-emerald-700"
              }`}>{n.type}</span>
              <span className="text-xs text-slate-400 flex items-center gap-1"><Calendar size={12} /> {n.date}</span>
            </div>
            <h3 className="font-bold text-ink mb-1">{n.title}</h3>
            <p className="text-sm text-slate-500">{n.desc}</p>
            <Link to={`/admin/notice/${n.id}`} className="inline-flex items-center gap-1 text-dav-brand font-semibold text-sm mt-2">
              View <ChevronRight size={14} />
            </Link>
          </div>
        ))}
      </div>

      <div className="mt-8 card p-6 flex items-center gap-3">
        <Bell size={18} className="text-dav-brand" />
        <div>
          <div className="text-sm font-semibold text-ink">Get notified on your dashboard</div>
          <p className="text-xs text-slate-400">Students, parents and teachers will see important notices in their dashboards automatically.</p>
        </div>
      </div>
    </div>
  );
}
