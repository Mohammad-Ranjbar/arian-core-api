# Project instructions

## ERP REST service sections

- In `app/Services/Erp/RestApi/`, every new or modified service that registers a REST ERP job must pass a meaningful Persian `section` describing the business operation.
- Use the same Persian section for synchronous (`send`) and queued (`enqueue`) execution of the same operation.
- Keep endpoint names, payload keys, permission identifiers, and other technical identifiers unchanged; translate the human-readable `section` only.
- The generic `RestErpJobService` section defaults to `ارتباط با ERP`; operation-specific services must provide their own descriptive Persian title.
- Do not rewrite historical database records as part of a section-label change unless explicitly requested.
- Inspect existing conventions and run the relevant tests after modifying these services. Respond in Persian by default.
