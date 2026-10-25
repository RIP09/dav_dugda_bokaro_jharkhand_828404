import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { GraduationCap, Mail, Lock, AlertCircle } from "lucide-react";
import { AUTH } from "@/lib/constants";

export default function Login() {
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    // In a real app this would call an API / Convex mutation.
    setTimeout(() => {
      setLoading(false);
      // Default: first registered user is admin
      navigate("/dashboard");
    }, 600);
  };

  return (
    <div className="min-h-screen bg-dav-brand flex">
      <div className="lg:w-1/2 p-10 md:p-16 flex flex-col justify-center text-white">
        <div className="max-w-md">
          <h1 className="text-3xl font-extrabold leading-tight">
            Welcome back to <span className="text-amber-300">DAV Dugda</span>
          </h1>
          <p className="text-white/70 mt-2">Sign in to your panel &mdash; student, parent, teacher, staff or admin.</p>

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
                  placeholder="you@example.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
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
                  placeholder="Your password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              </div>
            </div>
            <button
              type="submit"
              className="btn-brand-brand w-full justify-center"
              disabled={loading}
            >
              {loading ? "Signing in &hellip;" : "Sign In"}
            </button>
          </form>

          <p className="text-white/60 text-sm mt-4">
            New to DAV Dugda?{" "}
            <Link to="/signup" className="text-amber-300 font-semibold">Create an account</Link>
          </p>
        </div>
      </div>

      <div className="lg:w-1/2 bg-white flex items-center justify-center p-10">
        <div className="hidden lg:block w-1/2 relative">
          <div className="absolute -top-6 -right-6 w-48 h-48 bg-amber-300 rounded-full opacity-15 animate-pulse-glow" />
          <div className="absolute -bottom-6 -left-6 w-40 h-40 bg-dav-green rounded-full opacity-10" />
          <div className="relative bg-white/10 backdrop-blur rounded-2xl p-8 border border-white/10">
            <div className="text-4xl font-extrabold text-amber-300">{SCHOOL.name}</div>
            <p className="text-white/70 text-sm mt-1">DAV Public School, Dugda</p>
            <div className="mt-4 grid grid-cols-2 gap-3 text-white/60 text-xs">
              <div>Phone: {SCHOOL.phone}</div>
              <div>Email: {SCHOOL.email}</div>
            </div>
            <div className="mt-4 text-white/70 text-xs">{SCHOOL.address}</div>
          </div>
        </div>
      </div>
    </div>
  );
}
