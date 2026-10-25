import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import { ConvexProvider, ConvexReactClient } from "convex/react";
import { AuthProvider } from "./context/AuthContext";
import "./index.css";

const convex = new ConvexReactClient("https://dav-dugda-sms.convex.cloud");

ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <ConvexProvider client={convex}>
      <AuthProvider>
        <ConvexAuthProvider>
          <App />
        </ConvexAuthProvider>
      </AuthProvider>
    </ConvexProvider>
  </React.StrictMode>
);
