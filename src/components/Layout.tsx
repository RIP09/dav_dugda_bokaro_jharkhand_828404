import { Outlet } from "react-router-dom";
import { useState } from "react";
import { Menu, X, Crown, Users, GraduationCap, User, Home, BookOpen, Mail, Clock, FileCheck, TrendingUp, Bell } from "lucide-react";
import Navbar from "./Navbar";
import Sidebar from "./Sidebar";

export default function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className="min-h-screen flex flex-col">
      <Navbar onMenuClick={() => setSidebarOpen(true)} />
      <div className="flex flex-1 overflow-hidden">
        <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />
        <main className="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 scroll-margin">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
