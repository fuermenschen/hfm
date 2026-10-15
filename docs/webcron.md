# Production webcron

## Configuration

Configure cron-job.org to call `GET /queue-worker` every minute with the `X-API-Key`
header matching `APP_API_KEY`. Keep the provider timeout and hosting
`max_execution_time` at 300 seconds. The endpoint invokes Laravel's scheduler,
then drains queued work; its path and route name remain compatible with existing setup.

Required production settings:

```dotenv
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=120
CACHE_STORE=file
```

The worker uses `PHP_BINDIR.'/php'` as its CLI executable. Hosting must allow
subprocess execution and CLI PHP must have PCNTL enabled.
The effective web and CLI `max_execution_time` must be 0 (unlimited) or at least
120 seconds. A lower web limit still permits scheduler dispatch but skips the
worker with HTTP 503 and an application-log error. A synchronous queue is rejected
before scheduler dispatch to prevent inline maintenance work. CLI preflight rejects
missing PCNTL, insufficient execution time, or a queue reservation below 120 seconds.
The web and CLI runtimes must use the same application environment and cache store.

## Execution and monitoring

The worker stops when empty or after a 20-second budget checked between jobs.
Each job has a 60-second timeout; the CLI subprocess has a 90-second wall-clock
deadline. Job-specific timeouts must not exceed 60 seconds. Only one webcron worker
runs at a time, using a nonblocking 120-second cache lock; concurrent requests still
invoke the scheduler. Normal work on the configured database queue (`DB_QUEUE`,
default `default`) takes priority over image warming on `maintenance`. Both worker
and scheduler output appear in the plain-text response;
command failures return HTTP 500. Individual job failures can still leave a successful
worker exit: monitor application logs and `php artisan queue:failed` as well.

## Recurring tasks

Pending group requests for ended events are cleared weekly, Sundays at 01:00 UTC. Story images for verified
registrations of published, unended events are warmed at 01:00 UTC, including every
variant. Dispatch is paged in groups of 100 registrations; rendering runs in separate
jobs and reuses fingerprinted caches. Unique dispatch locks expire after an hour for
cleanup and 24 hours for warming; render locks expire after 120 seconds.
Atomic per-minute cache gates prevent repeated scheduler requests from redispatching
completed maintenance entry jobs during the same due minute.
If a render lock remains unavailable after 10 seconds, requests recheck the image
cache, then return HTTP 503 with `Retry-After: 120` if no image is ready.
Warming jobs release themselves for a retry 120 seconds later on contention,
rather than immediately consuming remaining attempts.

## Recovery and deployment

If execution is killed, committed work remains. Reserved jobs become retryable after
120 seconds from reservation and resume on a later minute, subject to backlog and
worker-lock expiry. Three failed attempts exhaust a job. Cleanup and warming tolerate
retries and partial completion. A missed scheduler tick is not replayed: cleanup waits
until the next Sunday, warming until the next night; on-demand images remain available.
Maintenance mode suspends webcron requests. Before deployment, pause external webcron
and allow any current worker to finish before clearing caches; cache clears can remove
coordination locks. Resume webcron after deployment.
