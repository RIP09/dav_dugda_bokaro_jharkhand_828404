#!/usr/bin/env bash
# Type-checks and runs the iOS Core (Foundation-only code) on Linux or macOS against a live server.
#   ./Tools/LinuxCheck/run.sh http://127.0.0.1:8090/api/v1/
set -euo pipefail
cd "$(dirname "$0")/../.."
SRC="DAVSchool/Core/Networking/*.swift DAVSchool/Core/Models/*.swift DAVSchool/Core/Session/AppConfig.swift DAVSchool/Core/Session/ModuleArea.swift DAVSchool/Core/Session/KeychainStore.swift DAVSchool/Modules/*/*Module.swift"
OUT="${TMPDIR:-/tmp}/dav-ios-core-check"
swiftc -O -o "$OUT" $SRC Tools/LinuxCheck/main.swift
"$OUT" "${1:-http://127.0.0.1:8090/api/v1/}"
