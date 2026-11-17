This component allows to assign teasers and further refine their content using a form on the teaser item. Other types of
teasers can be added using the `TeaserProviderRegistry`.

The component loads its items with a `TeaserStore` and therefore needs a running API, so it cannot be rendered in the
styleguide. The following props are available:

- `value`: Object with the `items` (`{type, id, title, description, mediaId, edited}`) and the `presentAs` key of the
  chosen presentation.
- `onChange`: Called with the new value after items were added, removed, sorted or edited.
- `locale`: Observable with the locale used to load and display the teasers.
- `presentations`: Optional list of `{label, value}` options. If given, a dropdown to choose how the teasers are
  presented is shown.
- `disabled`: Disables adding, removing and sorting of items.
- `onItemClick`: Optional callback called with the `id` and the item when an item is clicked.

```jsx static
<TeaserSelection
    disabled={false}
    locale={observable.box('en')}
    onChange={(value) => console.log(value)}
    onItemClick={(id, item) => console.log(id, item)}
    presentations={[
        {label: 'Left', value: 'left'},
        {label: 'Right', value: 'right'},
    ]}
    value={{
        items: [{id: '1', type: 'pages', title: 'Teaser title', description: 'Teaser description'}],
        presentAs: 'left',
    }}
/>
```
