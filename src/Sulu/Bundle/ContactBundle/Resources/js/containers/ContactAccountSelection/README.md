This container allows to select contacts and accounts in a single list. The `value` is an array of prefixed ids
(`c1` for the contact with id 1, `a2` for the account with id 2). A button with a dropdown opens a
[`MultiListOverlay`](#multilistoverlay) for either the contacts or the accounts. Selected items can be sorted and
removed, and `onChange` is called with the new array of prefixed ids.

The component loads the selected items from the API, so it cannot be rendered in the styleguide. The props are:

- `value`: Array of prefixed ids, defaults to an empty array.
- `onChange`: Called with the new array of prefixed ids.
- `onItemClick`: Optional callback called with the `itemId` and the item when an item is clicked.
- `disabled`: Disables adding, removing and sorting of items, defaults to `false`.

```jsx static
<ContactAccountSelection
    disabled={false}
    onChange={(value) => console.log(value)}
    onItemClick={(itemId, item) => console.log(itemId, item)}
    value={['c1', 'a2']}
/>
```
