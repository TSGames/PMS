#!/usr/bin/env bash
#
# Startet/stoppt den PHP-Entwicklungsserver für das Mock-System.
#
#   tests/mock/server.sh start|stop|restart|status|logs
#
# Der Server nutzt die von tests/mock/setup.php erzeugte PHP-Konfiguration
# (tests/.runtime/conf.d), damit die Extension-Auswahl dem Produktions-Image
# entspricht.

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
RUNTIME="$REPO_ROOT/tests/.runtime"
CONF_DIR="$RUNTIME/conf.d"
PID_FILE="$RUNTIME/server.pid"
LOG_FILE="$RUNTIME/logs/server.log"
HOST="${PMS_MOCK_HOST:-127.0.0.1}"
PORT="${PMS_MOCK_PORT:-8099}"

is_running() {
    [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null
}

start() {
    if is_running; then
        echo "Server läuft bereits (PID $(cat "$PID_FILE")) auf http://$HOST:$PORT"
        return 0
    fi
    if [ ! -d "$CONF_DIR" ]; then
        echo "Mock-System ist nicht eingerichtet. Bitte zuerst ausführen:" >&2
        echo "  php tests/mock/setup.php" >&2
        exit 1
    fi
    mkdir -p "$RUNTIME/logs"
    PHP_INI_SCAN_DIR="$CONF_DIR" php -S "$HOST:$PORT" -t "$REPO_ROOT/src" \
        "$REPO_ROOT/tests/mock/router.php" >"$LOG_FILE" 2>&1 &
    echo $! > "$PID_FILE"

    for _ in $(seq 1 50); do
        if curl -fs -o /dev/null "http://$HOST:$PORT/admin"; then
            echo "Server gestartet: http://$HOST:$PORT/admin.php (PID $(cat "$PID_FILE"))"
            return 0
        fi
        sleep 0.2
    done
    echo "Server antwortet nicht. Log:" >&2
    tail -20 "$LOG_FILE" >&2
    exit 1
}

stop() {
    if is_running; then
        kill "$(cat "$PID_FILE")"
        rm -f "$PID_FILE"
        echo "Server gestoppt."
    else
        rm -f "$PID_FILE"
        echo "Server läuft nicht."
    fi
}

case "${1:-start}" in
    start)   start ;;
    stop)    stop ;;
    restart) stop; start ;;
    status)
        if is_running; then
            echo "läuft (PID $(cat "$PID_FILE")) auf http://$HOST:$PORT"
        else
            echo "gestoppt"
            exit 1
        fi
        ;;
    logs)    tail -n "${2:-50}" "$LOG_FILE" ;;
    *)
        echo "Aufruf: $0 {start|stop|restart|status|logs}" >&2
        exit 1
        ;;
esac
