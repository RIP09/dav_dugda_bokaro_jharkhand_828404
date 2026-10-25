import { NavLink, useLocation } from "react-router-dom";
import { Crown, Users, GraduationCap, Home, BookOpen, Mail, Clock, FileCheck, TrendingUp } from "lucide-react";
import { ROLE_PAGES } from "@/lib/constants";

interface Props {
  open: boolean;
  onClose: () => void;
}

const menuItems = [
  { icon: Home, label: "Dashboard", path: "/dashboard" },
  { icon: Crown, label: "Admin", path: "/admin/dashboard" },
  { icon: Users, label: "Staff", path: "/staff/dashboard" },
  { icon: GraduationCap, label: "Teachers", path: "/teacher/dashboard" },
  { icon: User, label: "Parents", path: "/parent/dashboard" },
  { icon: BookOpen, label: "Students", path: "/student/dashboard" },
  { icon: Mail, label: "Messages", path: "/messages" },
  { icon: Clock, label: "Attendance", path: "/attendance" },
  { icon: FileCheck, label: "Exams & Results", path: "/exams" },
  { icon: TrendingUp, label: "Fees", path: "/fees" },
];

export default function Sidebar({ open, onClose }: Props) {
  const location = useLocation();

  return (
    <>
      {/* Overlay for mobile */}
      {open && (
        <div
          className="fixed inset-0 bg-black/30 z-30 lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      <aside
        className={`fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-line transform transition-transform duration-300 lg:transform-none ${
          open ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <div className="flex flex-col h-full p-4">
          <div className="flex items-center gap-2 mb-6 px-2">
            <span className="w-8 h-8 rounded-lg bg-dav-brand flex items-center justify-center text-white font-black">
              D
            </span>
            <span className="font-bold text-sm text-ink">DAV Dugda SMS</span>
          </div>

          <nav className="flex-1 space-y-1">
            {menuItems.map((item) => {
              const isActive = location.pathname === item.path;
              return (
                <NavLink
                  key={item.path}
                  to={item.path}
                  className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 ${
                    isActive
                      ? "bg-dav-brand text-white shadow-sm"
                      : "text-slate-600 hover:bg-line hover:text-ink"
                  }`}
                  onClick={onClose}
                >
                  <item.icon size={18} />
                  <span>{item.label}</span>
                </NavLink>
              );
            })}
          </nav>

          <div className="pt-4 border-t border-line">
            <div className="px-3 py-3 rounded-xl bg-maroon/5">
              <div className="text-sm font-semibold text-ink mb-1">{import.meta.env.VITE_USER_ROLE || "Admin"}</div>
              <div className="text-xs text-slate-400">{import.meta.env.VITE_USER_NAME || "Administrator"}</div>
            </div>
          </div>
        </div>
      </aside>
    </>
  );
}
