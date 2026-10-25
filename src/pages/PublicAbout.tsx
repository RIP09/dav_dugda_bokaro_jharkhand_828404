import { Link } from "react-router-dom";
import { GraduationCap, MapPin, Phone, Mail, Clock, Award, Users, School } from "lucide-react";
import { SCHOOL } from "@/lib/constants";

export default function PublicAbout() {
  return (
    <div className="max-w-7xl mx-auto px-4 md:px-8 py-10 scroll-margin">
      <div className="mb-10">
        <Link to="/" className="text-sm text-dav-brand hover:underline flex items-center gap-1">
          <span className="w-5 h-5 rounded bg-dav-brand flex items-center justify-center text-white text-xs">D</span>
          Home
        </Link>
        <span className="text-slate-300">/</span>
        <span className="text-sm font-semibold text-ink">About</span>
      </div>

      <div className="grid lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2">
          <h1 className="text-3xl font-bold text-ink mb-4">{SCHOOL.name}</h1>
          <p className="text-slate-500 mb-6">{SCHOOL.description}</p>

          <div className="grid sm:grid-cols-2 gap-6">
            <div className="card p-6">
              <div className="w-12 h-12 rounded-xl bg-dav-brand/10 flex items-center justify-center mb-4">
                <School size={20} className="text-dav-brand" />
              </div>
              <h3 className="font-bold text-ink mb-2">About the School</h3>
              <p className="text-sm text-slate-500">{SCHOOL.description} Established in {SCHOOL.founded}, it is a co-educational day school affiliated to the Central Board of Secondary Education (CBSE).</p>
            </div>
            <div className="card p-6">
              <div className="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center mb-4">
                <Award size={20} className="text-amber-600" />
              </div>
              <h3 className="font-bold text-ink mb-2">Academics</h3>
              <p className="text-sm text-slate-500">The curriculum blends the traditional rigour of the DAV system with modern learning methods — STEM, languages, arts and sports, all integrated into a values-driven environment.</p>
            </div>
          </div>
        </div>

        <div className="space-stalk">
          <div className="card p-6">
            <h3 className="font-bold text-ink mb-4">Quick Details</h3>
            <div className="space-y-3 text-sm">
              {[
                { icon: MapPin, label: "Address", value: SCHOOL.address },
                { icon: Phone, label: "Phone", value: SCHOOL.phone },
                { icon: Mail, label: "Email", value: SCHOOL.email },
                { icon: Clock, label: "Founded", value: `2000 (${SCHOOL.founded})` },
                { icon: School, label: "Listed under", value: "DAV Colleges & Managing Society" },
              ].map((f) => (
                <div key={f.label} className="flex items-start gap-3">
                  <f.icon size={15} className="text-dav-brand mt-0.5" />
                  <span>{f.label}: {f.value}</span>
                </div>
              ))}
            </div>
          </div>
          <div className="card p-6 mt-4">
            <h3 className="font-bold text-ink mb-3">Roles & Panels</h3>
            <p className="text-sm text-slate-500 mb-3">The public site connects to all login panels. Each role sees only its own data.</p>
            <div className="flex flex-wrap gap-2">
              {Object.entries({
                admin: "Admin", staff: "Staff", teacher: "Teacher", parent: "Parent", student: "Student",
              }).map(([k, v]) => (
                <Link key={k} to={`/login?role=${k}`} className="px-3 py-1.5 rounded-lg bg-dav-brand/10 text-dav-brand text-xs font-medium hover:bg-dav-brand/20 transition">
                  {v}
                </Link>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
