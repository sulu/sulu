The `Permissions` container renders the security contexts of a system grouped by bundle as [`Matrix`](#matrix) for
editing the permissions of a role. The contexts are loaded from the security context store, so it cannot be rendered
in this documentation.

| Prop       | Description                                                                                             |
|------------|---------------------------------------------------------------------------------------------------------|
| `value`    | Array of `{context, id, permissions}`, where `permissions` maps an action to a boolean.                 |
| `onChange` | Called with the new array of context permissions.                                                       |
| `system`   | System whose security contexts are shown, reloaded if it changes.                                       |
| `disabled` | Makes the matrices read only (default `false`).                                                         |

```jsx static
<Permissions
    disabled={false}
    onChange={this.handleChange}
    system="Sulu"
    value={[
        {context: 'sulu.security.roles', id: 1, permissions: {view: true, add: true, edit: false, delete: false}},
    ]}
/>
```
