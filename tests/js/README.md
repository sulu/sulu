# JavaScript Test Conventions

This codebase uses React Testing Library (RTL) for React tests.

## Current rules

- Use `@testing-library/react` and `@testing-library/user-event` for React test interaction.
- Assert visible behavior and user interaction whenever possible.
- Keep a consistent test structure: `arrange`, `act`, `assert` (single concern per test).
- Every React component test (`*/components/*/tests/*.test.js`) must contain at least one snapshot test.
- Snapshot tests for container tests are optional.

## Test structure convention

- Name tests by behavior, not implementation details.
- Keep setup local to the test or extracted into a small `create*` helper in the same file.
- Avoid asserting private state unless there is no public behavior path.
- Prefer queries by role/text/label over DOM class selectors in RTL tests.

## Useful command

- `npm test -- --watchman=false`
