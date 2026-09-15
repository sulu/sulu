# BlockCollection Test Fixtures

These XML template files are examples that reproduce the bug described in issue #8697.

## Files

- **menu.xml**: Template with 3 levels of nested blocks (pages → subpages → subpages2)
- **footer.xml**: Template with multiple nested block types (pages, socials)

## Context

When switching snippet templates in the admin panel, the block data from the old template might arrive as an object `{}` instead of an array `[]`. This caused crashes because array methods (`.map()`, `.filter()`, `.length`) were called directly on the object without type checking.

These templates demonstrate real-world scenarios with deeply nested block structures that trigger the bug.

## Related

- Issue: https://github.com/sulu/sulu/issues/8697
- PR: https://github.com/sulu/sulu/pull/8706

## Credits

Templates provided by @Leanid554 in the issue discussion.