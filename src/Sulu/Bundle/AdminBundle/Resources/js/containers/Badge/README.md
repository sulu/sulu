The `Badge` container renders a [`Badge`](#badge) component with a value it loads from the server. It is used for the
badges shown next to tab titles. The value is requested from the route `routeName` and reloaded whenever the route of
the passed [`Router`](#router) changes. It cannot be rendered without a running admin application, therefore the
example below is not executed.

| Prop                        | Description                                                                              |
|-----------------------------|------------------------------------------------------------------------------------------|
| `routeName`                 | Name of the Symfony route the badge value is requested from.                             |
| `router`                    | The `Router` service, its attributes can be added to the request.                        |
| `tabViewRoute`              | The `Route` of the tab the badge belongs to.                                             |
| `dataPath`                  | JSON pointer to the value inside of the response. The whole response is used if omitted. |
| `requestParameters`         | Static query parameters for the request.                                                 |
| `routerAttributesToRequest` | Maps router attributes to request parameters, e.g. `{id: 'id'}`.                         |
| `visibleCondition`          | JEXL expression, evaluated with `value`. The badge is hidden if it returns a falsy value. |

Nothing is rendered as long as the value is `null` or `undefined`.

```javascript static
<Badge
    dataPath="/total"
    requestParameters={{limit: 1}}
    routeName="sulu_page.get_pages"
    router={router}
    routerAttributesToRequest={{id: 'id'}}
    tabViewRoute={route}
    visibleCondition="value > 0"
/>
```
