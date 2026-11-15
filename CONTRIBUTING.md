# Contributing

## Setup

1. Install dependencies:

```bash
composer install
```

2. Run the quality checks:

```bash
composer quality
```

## Code Style

- Keep changes focused and small.
- Follow the existing namespace and file layout under `src/` and `tests/`.
- Prefer strict types and explicit value objects where the codebase already does.

## Testing

- Add or update unit tests for localized logic changes.
- Add or update integration tests for behavior that crosses multiple components.
- If a change affects generated regression snapshots, update the fixture under `tests/fixtures/generated/` and make sure the corresponding tests still pass.

### Suite split

- `composer test` — the default suite (fast unit + integration tests; ~2.5 min).
- `composer test:slow` — the `slow`-tagged property, oracle, boundary and year-scan suites.
- `composer test:all` — everything.
- Tag any new suite that takes more than a few seconds with `#[Group('slow')]`.

## Pull Requests

- Describe what changed and why.
- Mention any new scripts, fixtures, or assumptions.
- Include test results if the change affects behavior.

### Required CI checks

CI runs two jobs on every PR and before every `v*` tag: `build` (PHPStan +
default suite via `composer quality`) and `slow-tests` (`composer test:all`).
A `gate` job fails the run unless both succeed. Mark **both** `build`, `slow-tests`
and `gate` as required status checks in the GitHub branch-protection settings for
`main` (this can only be configured in the repository settings, not in YAML).
