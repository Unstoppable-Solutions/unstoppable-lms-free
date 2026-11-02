# unstoppable-lms

> This file is for Claude Code sessions working on this repo. For a human-oriented overview see `README.md`.

## ✅ This is a FREE plugin (WordPress.org)

- Distributed via **WordPress.org** plugin directory.
- Must NOT contain: license system, updater, review nags, upgrade nags, or any trialware.
- Pairs with a separate **Pro** repo: `Unstoppable-Solutions/unstoppable-lms` (private).

## What this plugin does

A clean, reliable Learning Management System for WordPress. Create courses, track progress, and manage students — without the bloat. Free version supports 1 course with 5 lessons, unlimited students.

- **Main PHP file:** `unstoppable-lms.php`
- **Code prefix:** `UNSTOPPABLE_LMS` (constants / class prefix); `unstoppable_lms` (lowercase for options / tables)
- **Package:** `Unstoppable_LMS`
- **WordPress.org slug:** `unstoppable-lms`
- **WP.org URL:** https://wordpress.org/plugins/unstoppable-lms/

## Base44 Pro mapping

- **Base44 app:** `https://fm-web-solutions-plugin-hub-d05f2182.base44.app` (Plugins entity for Pro version)
- When updating code that affects both the free (this repo) and the Pro repo, apply the matching change in both places in the same session.

## Free plugin rules that MUST hold

Per the Stand Alone Plugin Guide, these are **NEVER** allowed in this repo:
- ❌ License class and API
- ❌ Updater class
- ❌ "Check for Updates" button
- ❌ License reminder notice
- ❌ Upgrade-to-Pro notice (beyond a simple link)
- ❌ Review request notice
- ❌ Locked or trial features
- ❌ Custom CSS/JS/PHP input field
- ❌ Inline `<style>` or `<script>` tags

What IS required:
- ✅ Gold admin menu branding (enqueued CSS)
- ✅ Full security standards

## Release workflow

Releases are deployed to WordPress.org SVN automatically by GitHub Actions. The workflow is identical across every `unstoppable-solutions/*` plugin:

1. Finish feature work on `main`.
2. Bump the version in **all** of these:
   - `unstoppable-lms.php` — `Version:` header
   - `unstoppable-lms.php` — `UNSTOPPABLE_LMS_VERSION` constant
   - `readme.txt` — `Stable tag:`
   - `readme.txt` — add a `== Changelog ==` entry
3. Commit and push `main`.
4. On GitHub, create a new **Release** tagged `vX.Y.Z` (semver) with the changelog in the body.
5. The `deploy-to-wporg.yml` workflow fires and pushes the release to WP.org SVN.

## CI

Every push and PR runs the official WordPress Plugin Check against the codebase (`.github/workflows/plugin-check.yml`). CI must pass before merging. The deploy workflow (`.github/workflows/deploy-to-wporg.yml`) only fires on published Releases.

**Secrets used by deploy:** `SVN_USERNAME`, `SVN_PASSWORD` — org-level secrets on `unstoppable-solutions`. Nothing per-repo.

Both workflows carry an `if: ${{ ! github.event.repository.is_template }}` guard, so they are inert on the template itself (where placeholder tokens like `__PLUGIN_SLUG__` would otherwise fail Plugin Check parsing).

## Known phpcs:ignores specific to this plugin

_(none yet — add here as you ignore specific checks, one bullet per site, with the rationale.)_

## Shared conventions across the Unstoppable Solutions org

- **GPL-2.0-or-later** on everything.
- **One author** for Contributors: in `readme.txt`: `paramedic192` (Dave Stevens).
- **Tested up to** is bumped as WordPress ships each new .x.
- **Plugin Check CI config** (`.github/workflows/plugin-check.yml`) is standard across the org: `exclude-checks: late_escaping` + `exclude-files` lists the GitHub-only files (`.github`, `.distignore`, `.gitignore`, `.gitattributes`, `README.md`, `CLAUDE.md`, `SECURITY.md`, `CONTRIBUTING.md`, `CHANGELOG.md`).
- **`.distignore`** keeps GitHub-only files out of the WP.org zip (CI, docs, this file).
- **Prefer `phpcs:ignore` + inline rationale** over removing the ignored check from CI entirely — a specific ignore documents the decision at the call site; a check removal silently covers future code too.
- **Don't `-c user.email=` with a random identity** when committing — use `fieldmedics4@gmail.com` / `Dave Stevens` for authorship, with Claude as Co-Authored-By.

## Working with the plugin from Claude Code

- When Claude attaches this repo in a future session, this file loads automatically.
- Workflow for a bugfix / feature:
  1. Make the change on `main` (or a branch).
  2. Watch Plugin Check CI — do not merge red.
  3. For a release: follow the Release workflow section above.
- The free plugin and its Pro add-on live in **separate repos**. This repo is the free side; Pro is at `Unstoppable-Solutions/unstoppable-lms` (private).

## Repository layout

Standard WordPress plugin layout — `unstoppable-lms.php` is the bootstrap, `includes/` holds the classes, `assets/` holds CSS/JS, `languages/` holds the `.pot`, `readme.txt` is the WP.org directory listing, `.github/` holds the CI and deploy workflows.
