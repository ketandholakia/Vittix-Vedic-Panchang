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

## Pull Requests

- Describe what changed and why.
- Mention any new scripts, fixtures, or assumptions.
- Include test results if the change affects behavior.
