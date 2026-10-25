import { Link } from "react-router-dom";
import {
  GraduationCap, Users, BookOpen, Shield, Star, Award, Clock, MapPin, Phone, Mail, ChevronRight,
} from "lucide-react";
import { SCHOOL, HOUSES, DRESS, ROLE_PAGES } from "@/lib/constants";

export default function PublicLanding() {
  return (
    <div className="min-h-screen flex flex-col">
      {/* Hero */}
      <section className="bg-dav-brand text-white">
        <div className="max-w-7xl mx-auto px-4 md:px-8 py-16 md:py-24 lg:py-32">
          <div className="grid lg:grid-cols-2 gap-10 items-center">
            <div className="space-y-8">
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-white text-xs font-semibold border border-white/20">
                <Star size={14} className="animate-spin-slow" />
                CBSE Affiliated &middot; Established {SCHOOL.founded}
              </div>
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                Shaping Tomorrow{"'"}s <span className="text-amber-300">leaders</span>, today.
              </h1>
              <p className="text-lg text-white/80 max-w-lg">
                The complete DAV Public School, Dugda management suite — for students, parents, teachers, staff and full school administration.
              </p>
              <div className="flex flex-wrap gap-3">
                <Link to="/admission" className="btn-brand-brand">
                  <GraduationCap size={18} /> Admissions Open
                </Link>
                <Link to="/notice" className="btn-brand text-white border border-white/30 hover:bg-white/10">
                  <BookOpen size={18} /> School Noticeboard
                </Link>
              </div>
            </div>
            <div className="hidden lg:block relative">
              <div className="absolute -top-4 -right-4 w-48 h-48 bg-amber-300 rounded-full opacity-20 animate-pulse-glow" />
              <div className="absolute -bottom-4 -left-4 w-40 h-40 bg-dav-green rounded-full opacity-15" />
              <div className="relative bg-white/10 backdrop-blur rounded-2xl p-6 border border-white/10 shadow-2xl">
                <div className="text-5xl font-extrabold transparent text-amber-300 bg-clip-text">{SCHOOL.name}</div>
                <div className="text-white/70 text-sm mt-1">DAV Public School, Dugda</div>
                <div className="mt-4 flex items-center gap-2 text-white/60 text-xs">
                  <MapPin size={12} /> {SCHOOL.address}
                </div>
                <div className="mt-1 flex items-center gap-2 text-white/60 text-xs">
                  <Phone size={12} /> {SCHOOL.phone}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Houses intro */}
      <section className="py-12 md:py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 md:px-8">
          <div className="text-center max-w-2xl mx-auto mb-10">
            <h2 className="text-2xl md:text-3xl font-bold text-ink">House System of DAV Dugda</h2>
            <p className="text-slate-500 mt-2">Every student belongs to a house that competes in sports, culture and discipline through the year.</p>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {HOUSES.map((h) => (
              <div key={h.id} className="card p-6 text-center">
                <div className="w-14 h-14 rounded-full mx-auto mb-3" style={{ backgroundColor: `${h.colour}20` }}>
                  <div className="w-full h-full rounded-full border-2 border-white shadow" style={{ backgroundColor: h.colour }} />
                </div>
                <div className="font-bold text-ink text-sm">{h.name}</div>
                <div className="text-xs text-slate-400 mt-1">{h.mascot}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="py-12 md:py-16 bg-sand">
        <div className="max-w-7xl mx-auto px-4 md:px-8">
          <div className="text-center max-w-2xl mx-auto mb-10">
            <h2 className="text-2xl md:text-3xl font-bold text-ink">Everything the school needs, in one platform</h2>
            <p className="text-slate-500 mt-2">Complete full-stack management with separate dashboards for every stakeholder.</p>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {[
              { icon: GraduationCap, title: "Students", desc: "View classes, timetable, attendance, grades, notices, and exam results." },
              { icon: User, title: "Parents", desc: "Follow your child&apos;s attendance, marks, fees, and messages from teachers." },
              { icon: Users, title: "Teachers", desc: "Mark periods, upload grades, manage groups, and share notices with classes." },
              { icon: Crown, title: "Admin", desc: "Manage students, staff, fees, exams, attendance and edit content without touching code." },
              { icon: BookOpen, title: "Digital Noticeboard", desc: "Circulars, events, exam timetables and annual magazine in one place." },
              { icon: Shield, title: "Public Website", desc: "Brand page with admission, syllabus, uniform and contact for the wider community." },
            ].map((f) => (
              <div key={f.title} className="card p-5">
                <f.icon size={20} className="text-dav-brand-muted mb-3" />
                <h3 className="font-bold text-ink mb-1">{f.title}</h3>
                <p className="text-sm text-slate-500">{f.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-12 md:py-16 bg-dav-brand text-white">
        <div className="max-w-3xl mx-auto px-4 text-center">
          <h2 className="text-2xl md:text-3xl font-bold mb-4">Ready to manage DAV Dugda digitally?</h2>
          <p className="text-white/80 mb-8">Students, parents and staff — sign in to your panel. The school admin can publish everything from the website editor.</p>
          <div className="flex flex-wrap justify-center gap-4">
            <Link to="/login" className="btn-brand-group">
              <User size={18} /> Sign In
            </Link>
            <Link to="/admission" className="btn-brand text-white border border-white/30 hover:bg-white/10">
              <GraduationCap size={18} /> New Admission
            </Link>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-white border-t border-line py-8">
        <div className="max-w-7xl mx-auto px-4 md:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-400">
          <div>&copy; {new Date().getFullYear()} {SCHOOL.name}. All rights reserved.</div>
          <div className="flex items-center gap-4">
            <span className="flex items-center gap-1"><Phone size={12} /> {SCHOOL.phone}</span>
            <span className="flex items-center gap-1"><Mail size={12} /> {SCHOOL.email}</span>
            <span className="flex items-center gap-1"><MapPin size={12} /> {SCHOOL.address}</span>
          </div>
        </div>
      </footer>
    </div>
  );
}
