import { createContext, useContext, useEffect, useState, ReactNode } from "react";
import { User } from "convex/react";

interface AuthContextValue {
  user: User | null;
  loading: boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // In a Convex app, auth state is synced through useAuth() in the client.
    // For this standalone MVP we keep it as a placeholder until the Convex
    // auth integration is wired. The UI remains role-gated.
    const handle = setInterval(() => {
      setLoading(false);
      setUser(null);
    }, 0);
    return () => clearInterval(handle);
  }, []);

  return <AuthContext.Provider value={{ user, loading }}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
