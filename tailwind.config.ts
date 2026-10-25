import type { Config } from "tailwindcss";

export default {
  content: ["./index.html", "./src/**/*.{ts,tsx}"],
  theme: {
    extend: {
      colors: {
        dav: {
          brand: "#1a3a6b",
          brandLight: "#2e5496",
          white: "#f8fafc",
          offWhite: "#f1f5f9",
          fawn: "#e8c9a0",
          fawnDark: "#c69a6d",
          maroon: "#7a1f2b",
          maroonDeep: "#5c1621",
          green: "#2e7d4f",
          greenLight: "#4a9b6b",
          sand: "#fff7e6",
          gold: "#d9a441",
          ink: "#1e293b",
          slate: "#475569",
          line: "#cbd5e1",
        },
      },
      fontFamily: {
        sans: ["Inter", "system-ui", "sans-serif"],
      },
      container: {
        center: true,
      },
    },
  },
  plugins: [],
} satisfies Config;
