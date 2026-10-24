# Notes for Claude

Custom WordPress theme for gengiestastybites.ca. Read README.md (overview, backlog) and
DEPLOYMENT.md (pipeline, staging, troubleshooting) first.

- Change theme code here, test on staging, then merge to `main`. Never edit files on the server:
  the deploy drift check will block the next deploy.
- No local WordPress or PHP on this machine. Staging is the test environment; the deploy lints PHP on the server.
- SSH from Git Bash: `source scripts/lib.sh && remote '<command>'` (key auth, reads host/port/user from `.env`).
  Never print `.env` values or server credential files (`~/.staging-*`) into the conversation.
- The server's `wp` wrapper splits arguments on spaces even inside quotes. Keep `wp` arguments space-free.
- The host rate limits (HTTP 429) bursts of requests, and requests from the server to itself are
  throttled for minutes. Make HTTP checks from this machine, slowly.
- Production and staging share one cPanel account, one LiteSpeed cache store (`~/lscache`) and one MariaDB server.
  The staging DB is `<user>_staging`; scripts refuse to write to any other DB.
- Line endings are LF (`.gitattributes`); the server copy must match byte-for-byte for the drift check.
