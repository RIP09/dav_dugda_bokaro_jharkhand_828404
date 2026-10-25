import { Navigate, Route, Routes } from "react-router-dom";
import { useAuth } from "./context/AuthContext";
import Layout from "./components/Layout";
import PublicLanding from "./pages/PublicLanding";
import PublicAbout from "./pages/PublicAbout";
import PublicAdmission from "./pages/PublicAdmission";
import PublicNotice from "./pages/PublicNotice";
import Login from "./pages/Login";
import Signup from "./pages/Signup";
import AdminLogin from "./pages/AdminLogin";
import Dashboard from "./pages/Dashboard";

export default function App() {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-dav-brand">
        <div className="text-white text-2xl font-semibold animate-pulse">Loading DAV Dugda SMS &hellip;</div>
      </div>
    );
  }

  return (
    <Routes>
      {/* Public website */}
      <Route path="/" element={<PublicLanding />} />
      <Route path="/about" element={<PublicAbout />} />
      <Route path="/admission" element={<PublicAdmission />} />
      <Route path="/notice" element={<PublicNotice />} />
      <Route path="/contact" element={<PublicAbout />} />

      {/* Public landing diverted to login for logged-in users */}
      <Route path="/login" element={<AdminLogin />} />
      <Route path="/signup" element={<Signup />} />

      {/* Authenticated app panels */}
      <Route element={<Layout />}>
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/admin/*" element={<Navigate to="/admin/dashboard" replace />} />
        <Route path="/staff/*" element={<Navigate to="/staff/dashboard" replace />} />
        <Route path="/teacher/*" element={<Navigate to="/teacher/dashboard" replace />} />
        <Route path="/parent/*" element={<Navigate to="/parent/dashboard" replace />} />
        <Route path="/student/*" element={<Navigate to="/student/dashboard" replace />} />
      </Route>

      {/* Fallback */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
