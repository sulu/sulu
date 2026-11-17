The `ResourceLocatorHistory` component is used to show old URLs from different kind of resources. It is used in the
`resource_locator` and `custom_url` field types. Given the `resourceKey` and `id` props it can automatically show and
delete history URLs.

| Prop          | Description                                                                         |
|---------------|-------------------------------------------------------------------------------------|
| `resourceKey` | Resource whose history routes are loaded, e.g. `page_resourcelocators`.             |
| `id`          | ID of the entity. Nothing is shown if it is not set.                                |
| `options`     | Additional parameters, e.g. `{locale: 'en'}`, sent with the requests.               |

As it loads data from the server, the example is not executed.

```javascript static
<ResourceLocatorHistory id="b7e1f0c2" options={{locale: 'en'}} resourceKey="page_resourcelocators" />
```
