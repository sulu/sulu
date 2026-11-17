The component displaying and querying the search API. It shows a search field and the results of the query as list,
where each result has an icon, a title, a description and optionally an image and the locale. A click on a result
navigates to the route registered for the search resource of the result. The query is kept in the `searchStore` and
the available search resources are loaded from the API, so it cannot be rendered in this documentation.

| Prop     | Description                                                              |
|----------|--------------------------------------------------------------------------|
| `router` | The `Router`, used to navigate to the route of a clicked search result.  |

```jsx static
<Search router={router} />
```
