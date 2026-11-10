#!/usr/bin/env bash
# Start the results dashboard in the background.
#
#   ./dashboard-start.sh             serve on http://127.0.0.1:8765
#   PORT=9000 ./dashboard-start.sh   use another port
#
# Stop it with ./dashboard-stop.sh. Server log: build/dashboard.log
set -euo pipefail

ROOT=$(cd "$(dirname "$0")" && pwd)
HOST=127.0.0.1
PORT=${PORT:-8765}
PIDFILE=$ROOT/build/dashboard.pid
LOG=$ROOT/build/dashboard.log
URL="http://$HOST:$PORT/"

command -v php > /dev/null || { echo "php not found: brew install php" >&2; exit 1; }
mkdir -p "$ROOT/build"

# Already running? Report where instead of starting a second copy.
if [[ -f $PIDFILE ]]; then
  pid=$(cat "$PIDFILE")
  cmd=$(ps -p "$pid" -o command= 2> /dev/null || true)
  if [[ $cmd == *"php -S "*" -t $ROOT/dashboard"* ]]; then
    addr=$(awk '{print $3}' <<< "$cmd")
    echo "dashboard already running at http://$addr/ (pid $pid)"
    exit 0
  fi
  rm -f "$PIDFILE"
fi

if lsof -nP -iTCP:"$PORT" -sTCP:LISTEN > /dev/null 2>&1; then
  owner=$(lsof -nP -iTCP:"$PORT" -sTCP:LISTEN | awk 'NR == 2 {print $1 " (pid " $2 ")"}')
  echo "port $PORT is in use by $owner; try PORT=<other> $0" >&2
  exit 1
fi

nohup php -S "$HOST:$PORT" -t "$ROOT/dashboard" >> "$LOG" 2>&1 &
pid=$!
echo "$pid" > "$PIDFILE"

# Wait until it answers, or report why it died.
for _ in $(seq 1 50); do
  if curl -fs -o /dev/null "$URL"; then
    echo "dashboard running at $URL (pid $pid, log build/dashboard.log)"
    exit 0
  fi
  if ! kill -0 "$pid" 2> /dev/null; then
    rm -f "$PIDFILE"
    echo "dashboard failed to start; last log lines:" >&2
    tail -n 5 "$LOG" >&2
    exit 1
  fi
  sleep 0.1
done
echo "dashboard started (pid $pid) but $URL did not answer within 5s; see build/dashboard.log" >&2
exit 1
