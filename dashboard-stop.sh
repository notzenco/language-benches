#!/usr/bin/env bash
# Stop the dashboard started by ./dashboard-start.sh.
set -euo pipefail

ROOT=$(cd "$(dirname "$0")" && pwd)
PIDFILE=$ROOT/build/dashboard.pid

# True only for this project's dashboard server, so a reused pid or another
# php server is never killed.
is_dashboard() {
  local cmd
  cmd=$(ps -p "$1" -o command= 2> /dev/null || true)
  [[ $cmd == *"php -S "*" -t $ROOT/dashboard"* ]]
}

pids=()
if [[ -f $PIDFILE ]] && is_dashboard "$(cat "$PIDFILE")"; then
  pids=("$(cat "$PIDFILE")")
else
  # No usable pid file (e.g. build/ was deleted): find the server by its command.
  for pid in $(pgrep -f -- "-t $ROOT/dashboard" || true); do
    if is_dashboard "$pid"; then pids+=("$pid"); fi
  done
fi
rm -f "$PIDFILE"

if ((${#pids[@]} == 0)); then
  echo "dashboard is not running"
  exit 0
fi

for pid in "${pids[@]}"; do
  kill "$pid" 2> /dev/null || true
  for _ in $(seq 1 50); do
    kill -0 "$pid" 2> /dev/null || break
    sleep 0.1
  done
  if kill -0 "$pid" 2> /dev/null; then
    kill -9 "$pid" 2> /dev/null || true
  fi
  echo "dashboard stopped (pid $pid)"
done
