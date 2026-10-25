import { Bell, Menu, User } from "lucide-react";
import { Link, useNavigate } from "react-router-dom";

interface Props {
  onMenuClick: () => void;
}

export default function Navbar({ onMenuClick }: Props) {
  const navigate = useNavigate();

  return (
    <header className="bg-white border-b border-line sticky top-0 z-40">
      <div className="flex items-center justify-between px-4 md:px-6 h-16">
        <div className="flex items-center gap-3">
          <button
            className="lg:hidden p-2 rounded-lg hover:bg-line"
            onClick={onMenuClick}
            aria-label="Open menu"
          >
            <Menu size={22} className="text-ink" />
          </button>
          <Link to="/" className="flex items-center gap-2">
            <span className="w-9 h-9 rounded-lg bg-dav-brand flex items-center justify-center text-white font-black text-lg">
              D
            </span>
            <span className="text-sm font-semibold text-ink hidden sm:block">
              {import.meta.env.VITE_SCHOOL_SHORT || "DAV Dugda"} SMS
            </span>
          </Link>
        </div>

        <div className="flex items-center gap-2">
          {import.meta.env.VITE_USER_ROLE === "admin" && (
            <Link to="/admin/dashboard" className="p-2 rounded-lg hover:bg-line">
              <Crown size={18} className="text-amber-500" />
            </Link>
          )}
          <Link to="/dashboard" className="p-2 rounded-lg hover:bg-line hidden sm:flex">
            <Home size={18} className="text-ink" />
          </Link>
          <button className="relative p-2 rounded-lg hover:bg-line">
            <Bell size={18} className="text-ink" />
            <span className="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-maroon" />
          </button>
          <div className="flex items-center gap-2 pl-2 border-l border-line">
            <User size={16} className="text-slate-400" />
            <span className="text-sm font-medium text-ink hidden sm:block">
              {import.meta.env.VITE_USER_NAME || "Admin"}
            </span>
          </div>
        </div>
      </div>
    </header>
  );
}
