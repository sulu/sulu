The `ResourceLocatorHistory` component is used to show old URLs from different kind of resources. It is used in the
`resource_locator` and `custom_url` field types. Given the `resourceKey` and `options` props it can automatically show and
delete history URLs.

| Prop          | Description                                                                         |
|---------------|-------------------------------------------------------------------------------------|
| `resourceKey` | Resource whose history routes are loaded, e.g. `page_resourcelocators`.             |
| `disabled`    | Disables the button that opens the history, e.g. while the entity is not yet saved. |
| `options`     | Parameters sent with the requests, e.g. `{locale: 'en', resourceKey: 'pages', resourceId: 'b7e1f0c2'}`. |

As it loads data from the server, the example is not executed.

```javascript static
<ResourceLocatorHistory
    disabled={false}
    options={{locale: 'en', resourceId: 'b7e1f0c2', resourceKey: 'pages'}}
    resourceKey="page_resourcelocators"
/>
```
