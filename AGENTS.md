# Project instructions

## ERP REST service sections

- In `app/Services/Erp/RestApi/`, every new or modified service that registers a REST ERP job must pass a meaningful Persian `section` describing the business operation.
- Use the same Persian section for synchronous (`send`) and queued (`enqueue`) execution of the same operation.
- Keep endpoint names, payload keys, permission identifiers, and other technical identifiers unchanged; translate the human-readable `section` only.
- The generic `RestErpJobService` section defaults to `ارتباط با ERP`; operation-specific services must provide their own descriptive Persian title.
- Do not rewrite historical database records as part of a section-label change unless explicitly requested.
- Inspect existing conventions and run the relevant tests after modifying these services. Respond in Persian by default.

## REST ERP execution modes

- All POST endpoint services expose `dispatch(...)` for configuration-driven execution. Set `mode` under `rest_erp.endpoints.<endpoint>` to `sync`, `parallel`, or `unique`; optionally override one dispatch with `mode: RestErpExecutionMode::Unique` (or `Sync` / `Parallel`).
- `sync` executes immediately; `parallel` and `unique` use those existing queue names on `rest_erp.connection`. The `unique` queue is serial because Horizon runs one worker; it is not Laravel's `ShouldBeUnique` contract. Parallelism requires multiple workers, not just a queue name.
- Endpoint `send(...)` prepares a `PendingRestErpRequest` without side effects; finish the chain with `->sync()`, `->parallel()`, or `->unique()`, each returning the result array. Never dispatch implicitly in a destructor. Keep `send(...)->sync()` and ReturnRequests `sendOrRetry()` explicitly synchronous wherever local changes require ERP success. Existing `queue()` methods explicitly enqueue to `parallel`; use `dispatch()` in configurable workflows. The production-board GET requires an immediate response and stays synchronous.
- Retry preserves the job's persisted connection and queue. Configuration changes affect subsequent dispatches, not jobs already waiting or running.
- A synchronous dependent operation requires its parent to have succeeded. Do not configure a synchronous child behind an asynchronous parent in the same submission.
- When queue delivery is used, return 202 and tracking IDs; acceptance is not ERP success. Dispatch queued jobs only after commit. Do not add intermediate ActionLog entries.

- Each prepared fluent request executes once: repeating the same terminal method reuses its result (or exception); selecting another mode afterward fails. Create a new request for a deliberate new attempt.

- All internally authored REST ERP messages (including validation and exceptions) must be meaningful Persian. Preserve endpoint names, error codes, and original provider/third-party diagnostic details. Localize REST ERP validation without changing the application-wide locale.
