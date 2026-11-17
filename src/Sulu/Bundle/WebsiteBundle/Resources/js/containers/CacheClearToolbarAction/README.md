The `CacheClearToolbarAction` is a toolbar action, not a React component. It adds a button to the toolbar which opens a
confirmation [`Dialog`](#dialog); after the confirmation a `DELETE` request is sent to the endpoint stored in the static
`clearCacheEndpoint` property, which is set by the bundle on initialization. The optional `webspaceKey` passed to the
constructor is sent as query parameter and shown in the dialog text to clear only the cache of one webspace.

| Method                 | Description                                                                |
|------------------------|----------------------------------------------------------------------------|
| `getToolbarItemConfig` | Returns the config of the toolbar button, opening the dialog on click.     |
| `getNode`              | Returns the confirmation dialog, which has to be rendered by the view.     |

```jsx static
CacheClearToolbarAction.clearCacheEndpoint = '/admin/website/cache';

const toolbarAction = new CacheClearToolbarAction('sulu_io');

// part of the toolbar config
toolbarAction.getToolbarItemConfig();

// rendered somewhere in the view
toolbarAction.getNode();
```
