The `RoleAssignments` container is the field type for assigning roles to a user. It renders a table with one row per
assigned role, where the locales of the role can be chosen, and an overlay to add further roles. The available roles
and localizations are loaded from the API, so it cannot be rendered in this documentation.

| Prop       | Description                                                                  |
|------------|------------------------------------------------------------------------------|
| `value`    | Array of `{role: {id, ...}, locales: Array<string>}`.                        |
| `onChange` | Called with the new array of role assignments.                               |
| `disabled` | Disables adding, removing and changing the locales (default `false`).        |

```jsx static
<RoleAssignments
    disabled={false}
    onChange={this.handleChange}
    value={[
        {role: {id: 1, name: 'User'}, locales: ['en', 'de']},
        {role: {id: 2, name: 'Editor'}, locales: ['en']},
    ]}
/>
```
