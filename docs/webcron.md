# Production webcron

Call `GET /queue-worker` every minute from cron-job.org with `X-API-Key` matching
`APP_API_KEY`. Use `QUEUE_CONNECTION=database` and a persistent cache with atomic
locks, such as `CACHE_STORE=file`. Queue reservation defaults to 90 seconds
(`DB_QUEUE_RETRY_AFTER`); keep it at least 90 seconds.

## Execution

Scheduler and queue worker run sequentially inside the HTTP request using
`Artisan::call()`. No subprocess, CLI executable, `proc_open`, or PCNTL is required.
Scheduler runs first so due jobs can be processed immediately.

Jobs are assumed to finish within **30 seconds**, including I/O and lock waits.
Worker budget is the remainder of a minute after bootstrap/scheduling, minus
30 seconds for the last job and 5 seconds for the response. A smaller configured
PHP execution limit reduces this budget; insufficient time returns HTTP 503.
The budget is checked between jobs, not a hard wall-clock deadline.

A nonblocking 90-second worker lock prevents concurrent workers. Requests still
run the scheduler while another worker is active. Normal work on the configured
`DB_QUEUE` takes priority over `maintenance`. Command failures return HTTP 500;
individual failed jobs are recorded separately (`php artisan queue:failed`).

## Maintenance and recovery

- Pending group requests for ended events are cleared Sundays at 01:00 UTC.
- Story images for verified registrations of published, unended events are warmed
  nightly at 01:00 UTC, with paged dispatch and fingerprinted cache reuse.
- Unique jobs and per-minute dispatch gates suppress duplicates. Rendering retains
  its own locks; contention returns retryable HTTP 503 or delays queued warming.
- Killed requests leave jobs reserved until reservation expiry. Later webcron
  requests retry them, subject to worker-lock expiry and three-attempt limit.
- Missed scheduler ticks are not replayed; work waits for the next scheduled run.

Pause webcron and let the current request finish before deployment/cache clearing,
then resume. Clearing caches removes coordination locks.
