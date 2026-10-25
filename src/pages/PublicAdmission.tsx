import { Link } from "react-router-dom";
import { GraduationCap, Calendar, Users, FileCheck, ChevronRight, Shield } from "lucide-react";
import { ADMISSIONS, FEE_STRUCTURE } from "@/lib/enrollment";
import { SCHOOL } from "@/lib/constants";

export default function PublicAdmission() {
  return (
    <div className="max-w-7xl mx-auto px-4 md:px-8 py-10 scroll-margin">
      <div className="mb-10">
        <Link to="/" className="text-sm text-dav-brand hover:underline flex items-center gap-1">
          <span className="w-5 h-5 rounded bg-dav-brand flex items-center justify-center text-white text-xs">D</span>
          Home
        </Link>
        <span className="text-slate-300">/</span>
        <span className="text-sm font-semibold text-ink">Admissions</span>
      </div>

      <div className="grid lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2">
          <h1 className="text-3xl font-bold text-ink mb-4">Admissions 2027-28</h1>
          <p className="text-slate-500 mb-8">{SCHOOL.description} {SCHOOL.board.toLowerCase()}-affiliated, managed by DAV College Trust and Management Society.</p>

          <div className="grid md:grid-cols-2 gap-5">
            {ADMISSIONS.map((adm) => (
              <div key={adm.id} className="card p-6">
                <div className="flex items-start justify-between">
                  <div className="w-10 h-10 rounded-xl bg-dav-brand/10 flex items-center justify-center">
                    <GraduationCap size={18} className="text-dav-brand" />
                  </div>
                  {adm.status === "open" ? (
                    <span className="px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Open</span>
                  ) : (
                    <span className="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold">Closed</span>
                  )}
                </div>
                <h3 className="font-bold text-ink mt-4">{adm.name}</h3>
                <div className="flex items-center gap-4 text-sm text-slate-500 mt-1">
                  <span className="flex items-center gap-1"><Calendar size={14} /> Posted: {adm.posted}</span>
                  {adm.deadline && <span className="flex items-center gap-1"><Clock size={14} /> Deadline: {adm.deadline}</span>}
                </div>
                <div className="text-sm text-slate-500 mt-1">Allotted so far: {adm.allotted}</div>
                {adm.formLink && (
                  <Link to={adm.formLink} className="inline-flex items-center gap-1 text-dav-brand font-semibold mt-3">
                    Apply Now <ChevronRight size={14} />
                  </Link>
                )}
              </div>
            ))}
          </div>

          <div className="mt-8 card p-6">
            <h3 className="font-bold text-ink mb-3">Fee Structure 2027-28 (per annum, approx.)</h3>
            <div className="grid sm:grid-cols-3 gap-4 text-sm">
              {Object.entries(FEE_STRUCTURE).map(([level, fees]) => (
                <div key={level} className="rounded-xl bg-slate-50 p-4">
                  <div className="font-semibold text-ink">{level}</div>
                  <div className="text-slate-500 mt-1">{fees.science} &nbsp;-&nbsp; {fees.commerce} &nbsp;-&nbsp; {fees.humanities}</div>
                  <div className="font-bold text-dav-brand mt-1">{fees.total}</div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="space-stalk lg:col-span-1">
          <div className="card p-6">
            <div className="w-10 h-10 rounded-xl bg-dav-brand/10 flex items-center justify-center mb-4">
              <Shield size={18} className="text-dav-brand" />
            </div>
            <h3 className="font-bold text-ink">School Quick Info</h3>
            <div className="text-sm text-slate-500 space-y-2 mt-3">
              <div><strong>{SCHOOL.address}</strong></div>
              <div><strong>{SCHOOL.phone}</strong></div>
              <div><strong>{SCHOOL.email}</strong></div>
              <div>Affiliation: {SCHOOL.board} &middot; Since {SCHOOL.founded}</div>
            </div>
            <div className="mt-4 pt-4 border-t border-line">
              <h4 className="text-xs font-semibold text-slate-400 uppercase mb-2">For queries</h4>
              <a href={`mailto:${SCHOOL.email}`} className="text-sm text-dav-brand font-medium">{SCHOOL.email}</a>
              <a href={`tel:${SCHOOL.phone}`} className="text-sm text-dav-brand font-medium mt-1 block">{SCHOOL.phone}</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
