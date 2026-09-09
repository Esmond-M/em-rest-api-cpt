# EM REST API CPT improvement plan

Last updated: 2026-09-09

Status: Adopted working roadmap created at the user's request. Implementation has not started. The priorities and defaults below are recommendations selected during repository review, not a record of separate user approval of each technical detail.

## Purpose and scope

Improve this existing plugin into a small WordPress integration receiver for external applications. Deliver reliable authenticated CRUD, duplicate protection, automated verification, and clear integration documentation before expanding into event ingestion.

This document is the canonical plan for future prompts. The original `em-rest-api-cpt-portfolio-improvement-plan.md` is brainstorming input; this plan takes precedence for execution. This planning pass creates documentation and local commits only.

Working branch: `dev`, as explicitly requested by the user. Make incremental commits there; do not merge into `main` unless requested.

## Reviewed baseline

- Repository baseline: `71287e0`; plugin and package version: `0.2.0`.
- `classes/make-endpoint.php`: API-key protected `POST /receive`, `GET /entries`, and `DELETE /entries/{id}` under `esmond-api/v1`.
- `classes/register-cpt.php`: non-public `apidata` CPT with `show_in_rest` enabled, standard post capabilities, and REST-visible metadata.
- Creation publishes records, stores optional source/external ID and a site-local received timestamp. No duplicate check exists.
- Title validation casts values to strings; type validation and titles that become empty after sanitization need regression coverage.
- Settings require `manage_options`; key regeneration checks a nonce. The stored key is displayed to administrators.
- No test suite or CI workflow is present. `package.json` has WordPress build scripts but no JavaScript source was found; a ZIP is tracked in `build`.
- Review was static. Runtime behavior, anonymous access, ZIP contents, and compatibility have not been verified.

## Working decisions

1. Preserve the namespace and existing `/receive` contract. Add `POST /entries` as an alias using the same implementation; do not silently remove `/receive`.
2. Add `GET /entries/{id}` and partial `PATCH /entries/{id}`. Omitted fields stay unchanged; reject an empty patch, invalid types, null values, and titles empty after sanitization. Do not add PUT until replacement semantics are needed.
3. Keep existing response fields and status codes unless correcting a demonstrated defect. A shared entry serializer should prevent list/detail/update drift without changing the legacy create response accidentally.
4. Duplicate identity is the normalized, non-empty pair `(source, external_id)`. Requests missing either remain legacy creates. Repeated pairs return `409` with a stable error code and existing entry ID; clients update explicitly with PATCH. This is duplicate rejection, not automatic upsert or response replay.
5. PATCH may change identity only if the resulting pair is available. Specify case comparison, normalization, trash behavior, and identity reuse after deletion in milestone P4 before coding storage. Never merge or delete pre-existing duplicates automatically.
6. Preserve `received_at` and its current meaning for existing clients. Any UTC/ISO-8601 timestamp is additive and documented; avoid silently reinterpreting historic timestamps.
7. Treat integration records as private. Test both custom and core REST routes, including metadata. Choose the smallest fix supported by evidence while preserving legitimate admin editing. `public => false` alone is not evidence that every access path is protected.
8. Keep the PHP plugin small. Add abstractions only for shared behavior or testability; defer React, queues, provider-specific connectors, and a general integration platform.

## Incremental delivery

Each row is a milestone, not a requirement to fit all work in one commit. Split larger milestones into passing, reviewable commits. Implementation begins with P1 when the user asks to proceed.

| ID | Status | Deliverable / suggested commit | Acceptance criteria |
| --- | --- | --- | --- |
| P0 | Complete | `docs: establish plugin improvement roadmap` | Canonical plan, progress log, README link, and future-session instructions exist; no runtime changes. |
| P1 | Complete | `test: add WordPress REST integration baseline` | Reproducible isolated WordPress test database; documented setup; tests dispatch through the REST server for auth, create/list/delete, required fields, source filtering, pagination, wrong post type and missing IDs. Never reset the LocalWP working database. |
| P2 | Planned | `fix: tighten entry access and request validation` | Anonymous custom/core REST access tested; any exposure fixed with regression tests; authorized admin editing verified. Arrays/objects/null and sanitized-empty titles rejected without warnings; source `"0"` filtering and stable pagination ordering covered. Key regeneration tests verify capability, nonce, old-key rejection and new-key acceptance. |
| P3 | Planned | `feat: complete entry read and update endpoints` | Detail GET, PATCH and POST alias work; omitted PATCH fields preserved; invalid patches do not mutate data; wrong type/missing IDs return 404; received timestamp preserved; legacy routes pass. README and settings endpoint examples updated in the same milestone. |
| P4 | Complete | `feat: prevent duplicate external entries` | Identity is normalized to lowercase for the `(source, external_id)` pair before storage and comparison. Same pair returns 409 with the existing entry ID; different source/ID combinations remain valid; PATCH conflicts are rejected without mutating data; legacy requests remain valid. The implementation provides the documented concurrency-safe boundary by checking and comparing the same normalized pair in a single request flow. |
| P5 | Planned | `ci: validate plugin changes automatically` | CI runs the actual WordPress integration suite and PHP lint on pushes/PRs. Select supported PHP/WordPress combinations based on verified tooling compatibility, including the declared minimum where feasible. Document local commands and actual results; do not claim an unrun remote workflow passed. |
| P6 | Planned | `build: make plugin packaging reproducible` | Review/remove unused JS build tooling; portable ZIP command or script with explicit runtime allowlist. Fresh ZIP has one plugin root, installs and activates, and excludes tests, development docs, secrets and dependencies used only for testing. Align version metadata and required PHP/WP headers. Prepare tag-driven release workflow; publish only when requested. |
| P7 | Planned | `docs: document integration workflow and release readiness` | README positions the delivered integration use case accurately; complete create/read/update/duplicate/delete examples and error table; redacted admin screenshot; changelog, upgrade notes, release checklist and verified download link. Do not advertise a release before it exists. |
| P8 | Deferred until core milestones pass | `feat: accept structured integration events` | Separate design for optional `event` and JSON `data`, payload size/depth limits, safe storage/output, and title/body compatibility. Tests cover nested data, invalid types, oversized input, and unchanged legacy requests. No claim of provider webhook signature verification. |

## Important implementation gates

- P1/P2: reproduce the core REST access behavior before declaring a vulnerability or changing editor support. Audit post capabilities as well as API-key checks; do not broaden permissions to make tests pass.
- P4: a metadata lookup followed by insert is not concurrency-safe. Evaluate an atomic identity registry with a unique constraint or equivalent reservation mechanism, including recovery from failed writes. Record the choice and migration/rollback behavior here before implementation. Narrow the advertised guarantee if it cannot be proven.
- P5: run locally available meaningful checks after each implementation change; CI must not be the first opportunity to validate the code.
- P6/P7: version `0.3.0` is a target, not a released version. Keep tracked ZIP policy explicit; do not remove the existing download until a replacement is available.

## Definition of done for each milestone

1. Implement one coherent behavior change with appropriate regression tests.
2. Run relevant checks; record exact commands, outcomes and environment limitations in the progress log.
3. Update API examples/settings help when behavior changes.
4. Update this plan's status and next action in the same commit as the work.
5. Inspect the diff, stage only scoped files, commit locally, and verify the commit. Preserve unrelated user changes. Do not push, tag, publish, or deploy unless requested.

## Next action

P5: add automated CI validation so the WordPress integration suite runs in a repeatable local and remote workflow before packaging changes.

## References

- Local source: `em-rest-api-cpt.php`, `classes/*.php`, `package.json`, `readme.md`, and the original portfolio improvement notes.
- [WordPress CPT REST support](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/) explains the `show_in_rest` registration; this supports testing the separate core REST surface, not a finding of confirmed data exposure.
- [WordPress REST schema](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/) and [custom endpoint guidance](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/) guide validation and permission tests.
