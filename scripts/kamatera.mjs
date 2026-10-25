#!/usr/bin/env node
/**
 * Kamatera cloud integration for DAV Dugda SMS.
 *
 * Manages the Kamatera VPS that hosts the school management system
 * (public website + management panels). Uses the Kamatera Cloud API
 * (https://console.kamatera.com/service/...).
 *
 * Required keys (Settings -> Environment / Keys tab):
 *   KAMATERA_API_CLIENT_ID  - API client ID from Kamatera console
 *   KAMATERA_API_SECRET     - API secret from Kamatera console
 *
 * Usage:
 *   node scripts/kamatera.mjs <command> [options]
 *
 * Commands:
 *   servers                 List all servers on the account
 *   options                 List server-creation options (datacenters, images, ...)
 *   create <args>           Create a new server (see --help)
 *   info <serverId>         Show details of one server
 *   power <serverId> <state>  on | off | restart
 *   terminate <serverId>    Permanently delete a server (--force to skip safety)
 */

const API = "https://console.kamatera.com/service";

function keys() {
  const clientId = process.env.KAMATERA_API_CLIENT_ID;
  const secret = process.env.KAMATERA_API_SECRET;
  if (!clientId || !secret) {
    console.error(
      "Missing Kamatera API keys.\n" +
        "Add these in Settings -> Environment (Keys tab):\n" +
        "  KAMATERA_API_CLIENT_ID  (API client ID from https://console.kamatera.com)\n" +
        "  KAMATERA_API_SECRET     (API secret from https://console.kamatera.com)"
    );
    process.exit(1);
  }
  return { clientId, secret };
}

async function call(method, path, body, form = false) {
  const { clientId, secret } = keys();
  const headers = { clientId, secret };
  let payload;
  if (body && form) {
    headers["Content-Type"] = "application/x-www-form-urlencoded";
    payload = new URLSearchParams(body).toString();
  } else if (body) {
    headers["Content-Type"] = "application/json";
    payload = JSON.stringify(body);
  }
  const res = await fetch(`${API}${path}`, { method, headers, body: payload });
  const text = await res.text();
  let data;
  try { data = JSON.parse(text); } catch { data = text; }
  if (!res.ok) {
    console.error(`Kamatera API error ${res.status} on ${method} ${path}:`);
    console.error(typeof data === "string" ? data : JSON.stringify(data, null, 2));
    process.exit(1);
  }
  return data;
}

function parseFlags(args) {
  const flags = {};
  const positional = [];
  for (let i = 0; i < args.length; i++) {
    const a = args[i];
    if (a.startsWith("--")) {
      const key = a.slice(2);
      const next = args[i + 1];
      if (next === undefined || next.startsWith("--")) flags[key] = true;
      else { flags[key] = next; i++; }
    } else positional.push(a);
  }
  return { flags, positional };
}

async function main() {
  const [cmd, ...rest] = process.argv.slice(2);
  const { flags, positional } = parseFlags(rest);

  switch (cmd) {
    case "servers": {
      const servers = await call("GET", "/servers");
      if (!servers.length) console.log("No servers on this account.");
      else console.table(servers.map((s) => ({
        id: s.id, name: s.name, datacenter: s.datacenter, power: s.power,
      })));
      break;
    }

    case "options": {
      const opts = await call("GET", "/server");
      console.log("Datacenters:", Object.keys(opts.datacenter || {}).join(", "));
      if (flags.images) {
        console.log("\nDisk images (per datacenter):");
        for (const [dc, images] of Object.entries(opts.diskImages || {})) {
          console.log(` ${dc}: ${images.length} images, e.g. ${images[0]?.description || "-"}`);
        }
      } else {
        console.log("Pass --images to list OS images per datacenter.");
      }
      console.log("CPU examples:", (opts.cpu || []).slice(0, 5).join(", "));
      console.log("RAM (MB) examples:", (opts.ram?.B || []).slice(0, 5).join(", "));
      console.log("Traffic packages:", (opts.traffic?.EX || []).map((t) => t.id).join(", "));
      break;
    }

    case "create": {
      const name = flags.name;
      const diskSrc = flags["disk-src"];
      const datacenter = flags.datacenter || "US";
      const cpu = flags.cpu || "1B";
      const ram = String(flags.ram || 2048);
      const password = flags.password;
      const billing = flags.billing || "monthly";
      const traffic = flags.traffic || "t5000";
      const diskSize = String(flags["disk-size"] || 20);
      if (!name || !diskSrc || !password) {
        console.error(
          "create requires: --name <server-name> --disk-src <image-id> --password <root-password>\n" +
            "Optional: --datacenter US --cpu 1B --ram 2048 --billing monthly --traffic t5000 --disk-size 20"
        );
        process.exit(1);
      }
      const taskId = await call("POST", "/server", {
        disk_src_0: diskSrc,
        datacenter,
        name,
        cpu,
        ram,
        password,
        network_name_0: "wan",
        billing,
        traffic,
        disk_size_0: diskSize,
        ...(flags.backup ? { backup: true } : {}),
        ...(flags.managed ? { managed: true } : {}),
        ...(flags["ssh-key"] ? { selectedSSHKeyValue: flags["ssh-key"] } : {}),
      });
      console.log(`Server creation started. Task ID: ${taskId}`);
      console.log("Check progress with: node scripts/kamatera.mjs servers");
      break;
    }

    case "info": {
      const id = positional[0];
      if (!id) { console.error("info requires a server id"); process.exit(1); }
      console.log(JSON.stringify(await call("GET", `/server/${id}`), null, 2));
      break;
    }

    case "power": {
      const id = positional[0];
      const state = positional[1];
      if (!id || !["on", "off", "restart"].includes(state)) {
        console.error("power requires: <serverId> <on|off|restart>");
        process.exit(1);
      }
      const taskId = await call("PUT", `/server/${id}/power`, { power: state }, true);
      console.log(`Power '${state}' requested. Task ID: ${taskId}`);
      break;
    }

    case "terminate": {
      const id = positional[0];
      if (!id) { console.error("terminate requires a server id"); process.exit(1); }
      const body = { confirm: "1" };
      if (flags.force) body.force = "1";
      const taskId = await call("DELETE", `/server/${id}/terminate`, body);
      console.log(`Server termination started. Task ID: ${taskId}`);
      break;
    }

    default:
      console.log("Usage: node scripts/kamatera.mjs <servers|options|create|info|power|terminate> [args]");
      console.log("Keys required: KAMATERA_API_CLIENT_ID, KAMATERA_API_SECRET (Settings -> Environment)");
  }
}

main().catch((err) => {
  console.error(err.message || err);
  process.exit(1);
});
