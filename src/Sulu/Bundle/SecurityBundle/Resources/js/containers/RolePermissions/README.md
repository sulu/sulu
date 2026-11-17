The `RolePermissions` container renders one permission [`Matrix`](#matrix) per security system, listing the roles of
that system. It is used to edit the permissions of a single resource, for example a page or collection, per role. The
roles are loaded from the API and the available actions from the security context store, so it cannot be rendered in
this documentation.

| Prop              | Description                                                                                         |
|-------------------|-----------------------------------------------------------------------------------------------------|
| `value`           | Map of role id to a map of action to boolean, e.g. `{'1': {view: true, edit: false}}`.             |
| `onChange`        | Called with the new value.                                                                          |
| `resourceKey`     | Resource whose actions are shown, e.g. `pages`.                                                     |
| `system`          | System of the resource; limits the shown matrices together with `permissionCheck`.                  |
| `permissionCheck` | If `true` the matrices of the `Sulu` system and `system` are shown, otherwise only the `Sulu` one if `system` is set. |
| `webspaceKey`     | Replaces the webspace placeholder in the security context.                                          |
| `disabled`        | Makes the matrices read only (default `false`).                                                     |

```jsx static
<RolePermissions
    disabled={false}
    onChange={this.handleChange}
    permissionCheck={true}
    resourceKey="pages"
    system="Sulu"
    value={{'1': {view: true, edit: true, delete: false}}}
    webspaceKey="sulu_io"
/>
```
