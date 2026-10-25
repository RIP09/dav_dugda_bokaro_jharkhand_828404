import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Mail, Lock, AlertCircle, GraduationCap, Users } from "lucide-react";
import { ROLE_PAGES } from "@/lib/constants";

// Admin credentials for DAV Public School Dugda
export const ADMIN_CREDENTIALS = {
  email: "admin@davdugda.org",
  password: "admin@123",
};

export default function AdminLogin() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: ADMIN_CREDENTIALS.email, password: "" });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    // Simulate API call. Replace with the real auth endpoint in production.
    setTimeout(() => {
      if (form.email === ADMIN_CREDENTIALS.email && form.password === ADMIN_CREDENTIALS.password) {
        // Store a simple auth flag in sessionStorage
        sessionStorage.setItem("dav_admin_logged_in", "true");
        navigate("/admin/dashboard");
      } else {
        setError("Invalid admin credentials. Please try again.");
        setLoading(false);
      }
    }, 700);
  };

  return (
    <div className="min-h-screen bg-dav-brand flex">
      <div className="lg:w-1/2 p-10 md:p-16 flex flex-col justify-center text-white">
        <div className="max-w-md">
          <h1 className="text-3xl font-extrabold leading-tight">
            Admin Login — <span className="text-amber-300">DAV Dugda</span>
          </h1>
          <p className="text-white/70 mt-2">Manage the school, its people and the website content.</p>

          {error && (
            <div className="flex items-center gap-2 text-amber-300 text-sm mt-4 bg-white/10 rounded-xl p-3">
              <AlertCircle size={16} /> {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="mt-8 space-y-4">
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
            <p className="text-xs text-white/50 text-right">
              Demo credentials: <span className="text-white/70">admin@davdugda.org / admin@123</span>
            </p>
            <button
              type="submit"
              className="btn-brand-brand w-full justify-center"
              disabled={loading}
            >
              {loading ? "Signing in &hellip;" : "Sign In"}
            </button>
          </form>

          <p className="text-white/60 text-sm mt-4">
            Don&apos;t have access?{" "}
            <Link to="/signup" className="text-amber-300 font-semibold">Create an account</Link>
          </p>
        </div>
      </div>

      <div className="lg:w-1/2 bg-white flex items-center justify-center p-10">
        <div className="relative w-1/2 hidden lg:block">
          <div className="absolute -top-6 -right-6 w-48 h-48 bg-dav-green rounded-full opacity-10" />
          <div className="relative bg-white/10 backdrop-blur rounded-2xl p-8 border border-white/10 text-white">
            <div className="text-4xl font-extrabold">DAV Dugda SMS</div>
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
