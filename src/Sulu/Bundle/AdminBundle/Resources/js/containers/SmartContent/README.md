The `SmartContent` container lets the user define criteria (data source, categories, tags, audience targeting, types,
sorting, limit and presentation) and shows the items matching them. It is the base of the `smart_content` field type.
The criteria are held by a `SmartContentStore`, which also loads the result and is passed with the `store` prop. Which
criteria are offered is defined by the config of the store's `provider`, which is loaded by the
`smartContentConfigStore`. Because both load data from the server, the examples below are not executed.

| Prop               | Description                                                                                       |
|--------------------|---------------------------------------------------------------------------------------------------|
| `store`            | The `SmartContentStore` (`provider`, `filterCriteria`, `locale`, `dataSourceResourceKey`, `id`, `params`, `webspaceKey`) holding the filter criteria and the loaded items.                         |
| `defaultValue`     | `FilterCriteria` used when nothing was configured yet.                                            |
| `presentations`    | Array of `{name, value}`, offers a "Present as" select in the filter overlay.                     |
| `fieldLabel`       | Title of the filter overlay.                                                                      |
| `categoryRootKey`  | Key of the root category whose children are selectable.                                           |
| `disabled`         | Disables opening the filter overlay.                                                              |
| `onItemClick`      | Makes the items of the result clickable, called with the ID and the item.                         |

```javascript static
const store = new SmartContentStore('pages', {}, locale, 'pages', undefined, {}, 'sulu_io');

// the store only loads its items after `start()` was called
store.start();
// ... and has to be cleaned up with `store.destroy()` when the owning component unmounts

<SmartContent
    defaultValue={{includeSubFolders: false, limitResult: 5}}
    fieldLabel="Pages"
    presentations={[
        {name: 'Two columns', value: 'two'},
        {name: 'One column', value: 'one'},
    ]}
    store={store}
/>
```

The `FilterCriteria` have the following properties, all of them can be `undefined`:

| Property                           | Description                                          |
|------------------------------------|------------------------------------------------------|
| `dataSource`, `includeSubFolders`  | Root item and whether its sub items are included.    |
| `categories`, `categoryOperator`   | Category IDs, combined with `and` or `or`.           |
| `tags`, `tagOperator`              | Tags, combined with `and` or `or`.                   |
| `audienceTargeting`                | Whether audience targeting is applied.               |
| `types`                            | Types of the items, e.g. page templates.             |
| `sortBy`, `sortMethod`             | Property to sort by and `asc` or `desc`.             |
| `limitResult`                      | Maximum number of items.                             |
| `presentAs`                        | Value of the chosen presentation.                    |
