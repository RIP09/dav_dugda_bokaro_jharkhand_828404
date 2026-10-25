import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { GraduationCap, Mail, Lock, AlertCircle, User, Users } from "lucide-react";
import { ROLE_PAGES } from "@/lib/constants";

export default function Signup() {
  const navigate = useNavigate();
  const [role, setRole] = useState("student");
  const [form, setForm] = useState({ name: "", email: "", password: "" });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      navigate("/login");
    }, 800);
  };

  return (
    <div className="min-h-screen bg-dav-brand flex">
      <div className="lg:w-1/2 p-10 md:p-16 flex flex-col justify-center text-white">
        <div className="max-w-md">
          <h1 className="text-3xl font-extrabold leading-tight">
            Create your <span className="text-amber-300">DAV Dugda</span> account
          </h1>
          <p className="text-white/70 mt-2">Choose your role to access only the data you need.</p>

          <div className="mt-6 grid grid-cols-5 gap-2">
            {(Object.keys(ROLE_PAGES) as string[]).map((r) => (
              <button
                key={r}
                type="button"
                onClick={() => setRole(r)}
                className={`flex items-center justify-center gap-1.5 px-2 py-2 rounded-xl text-xs font-semibold transition ${
                  role === r ? "bg-amber-300 text-white" : "bg-white/10 text-white/60 hover:bg-white/20"
                }`}
              >
                {r === "admin" && <GraduationCap size={13} />}
                {r === "staff" && <Users size={13} />}
                {r === "teacher" && <GraduationCap size={13} />}
                {r === "parent" && <Users size={13} />}
                {r === "student" && <GraduationCap size={13} />}
                {r === "public" && <GraduationCap size={13} />}
                {r === "public" && "Web"}
                {r}
              </button>
            ))}
          </div>

          <form onSubmit={handleSubmit} className="mt-8 space-y-4">
            <div className="form-group">
              <label className="text-sm font-medium text-white/80">Full name</label>
              <input
                type="text"
                className="w-full px-4 py-3 rounded-xl bg-white/15 border border-white/20 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-amber-300"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label className="text-sm font-medium text-white/80">Email</label>
              <div className="relative">
                <Mail size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-white/40" />
                <input
                  type="email"
                  className="w-full pl-10 pr-4 py-3 rounded-xl bg-white/15 border border-white/20 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-amber-300"
                  value={form.email}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                  required
                />
              </div>
            </div>
            <div className="form-group">
              <label className="text-sm font-medium text-white/80">Password</label>
              <div className="relative">
                <Lock size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-white/40" />
                <input
                  type="password"
                  className="w-full pl-10 pr-4 py-3 rounded-xl bg-white/15 border border-white/20 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-amber-300"
                  value={form.password}
                  onChange={(e) => setForm({ ...form, password: e.target.value })}
                  required
                />
              </div>
            </div>
            <button
              type="submit"
              className="btn-brand-brand w-full justify-center"
              disabled={loading}
            >
              {loading ? "Creating account &hellip;" : "Create Account"}
            </button>
          </form>

          <p className="text-white/60 text-sm mt-4">
            Already have an account?{" "}
            <Link to="/login" className="text-amber-300 font-semibold">Sign in</Link>
          </p>
        </div>
      </div>

      <div className="lg:w-1/2 bg-white flex items-center justify-center p-10">
        <div className="relative w-1/2 hidden lg:block">
          <div className="absolute -top-6 -right-6 w-48 h-48 bg-dav-green rounded-full opacity-10" />
          <div className="relative bg-white/10 backdrop-blur rounded-2xl p-8 border border-white/10 text-white">
            <div className="text-4xl font-extrabold">{SCHOOL.name}</div>
            <p className="text-white/70 text-sm mt-1">DAV Public School, Dugda</p>
            <div className="mt-4 text-white/60 text-xs space-y-1">
              <div>{SCHOOL.address}</div>
              <div>{SCHOOL.phone}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
