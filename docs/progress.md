# Improvement progress

## 2026-09-09 - P0: planning baseline

- Reviewed plugin bootstrap, all three classes, README, package scripts, original improvement notes, repository status and recent history.
- Created the canonical roadmap, milestone acceptance criteria, and continuation instructions.
- Created and switched to `dev` at the user's request; future incremental work belongs on this branch.
- Added access-path verification, stricter input tests, compatibility rules, concurrency requirements, and packaging validation to the original suggestions.
- Validation: documentation links and `git diff --check`; no runtime code changed and no runtime tests were run.
- Original brainstorming file remains untouched and untracked; it is not required to follow the canonical plan.
- Next milestone: P1, isolated WordPress integration-test baseline.

## 2026-09-09 - P1: WordPress REST baseline setup

- Added an isolated `wp-env` test configuration and a PHPUnit bootstrap that targets the plugin’s WordPress test environment without touching the LocalWP site database.
- Added an initial REST integration suite covering auth failure, create/list/delete success, required title validation, source filtering and pagination, and missing/wrong-type IDs.
- Added PHP dependency management for the PHPUnit Polyfills requirement needed by the WP test suite and documented the local setup command in package scripts.
- Validation:
  - `npx --yes @wordpress/env start --update --config .wp-env.json` succeeded and started the WordPress dev and test sites.
  - `npx --yes @wordpress/env run tests-cli -- bash -lc "cd /var/www/html/wp-content/plugins/em-rest-api-cpt && composer install --no-interaction --no-progress"` was required before the suite could run because the WordPress test bootstrap needs PHPUnit Polyfills.
  - `npx --yes @wordpress/env run tests-cli -- bash -lc "cd /var/www/html/wp-content/plugins/em-rest-api-cpt && /home/PC/.composer/vendor/bin/phpunit --configuration ./phpunit.xml.dist --filter EM_REST_API_CPT_REST_Baseline_Test --testdox"` is the targeted validation command for the P1 milestone and was used after installing the dependency.
- Current status: P1 baseline is in place and passable under the isolated WordPress test environment. The local repeatable validation command is `npm run test:php`.
- Evidence: the final P1 verification command passed in the WP test container after aligning the PHPUnit version and the fixture metadata to WordPress conventions.
- Next milestone: P2 validation hardening and access-path regression work.

## 2026-09-09 - P4: duplicate identity guard

- Added a normalized identity check for the `(source, external_id)` pair before creating or patching an entry.
- Duplicate detection now compares lowercased values and returns a stable `rest_duplicate_entry` error with the existing entry ID when a conflict is found.
- PATCH updates reject identity swaps that would collide with another entry without mutating the original record.
- Validation:
  - `npx --yes @wordpress/env run tests-cli -- bash -lc "cd /var/www/html/wp-content/plugins/em-rest-api-cpt && ./vendor/bin/phpunit --configuration ./phpunit.xml.dist --testdox"` failed first on the new duplicate tests and then passed after the normalization fix.
- Current status: P4 duplicate protection is implemented and verified under the WordPress test environment.
- Next milestone: P5 CI validation workflow.

## 2026-09-09 - P5: automated CI validation

- Added a repeatable local PHP lint script and a GitHub Actions workflow that runs the plugin’s WordPress REST suite and PHP syntax checks on pushes and pull requests.
- Chosen verification path: PHP 8.2 and 8.3 matrix, npm install, composer install, then `npm run ci:check`.
- Validation:
  - `npm run lint:php` was run locally to confirm PHP files parse cleanly.
  - `npm run test:php` was run locally in the WordPress test container and passed with the project’s current suite.
- Current status: P5 CI validation is implemented and the local equivalent of the workflow has passed.
- Next milestone: P6 packaging reproducibility.

Commit history is the authoritative record of commit IDs; record prior IDs here when useful rather than trying to embed a commit's own hash in itself.
