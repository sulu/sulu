The `Menu` is a component to stylize a list. Usage examples of the `Menu` are the `Options` container inside the 
different `Select` component types and as the container for the `Suggestions` of the `AutoComplete`.

Example:

```
<Menu style={{maxWidth: '200px'}}>
    <li>Item 1</li>
    <li>Item 2</li>
    <li>Item 3</li>
</Menu>
```

A `Menu.Divider` separates groups of items, and `menuRef` receives the DOM node of the list.

```javascript
<Menu style={{maxWidth: '200px'}} menuRef={(ref) => console.log('menu node', ref)}>
    <li>Item 1</li>
    <Menu.Divider />
    <li>Item 2</li>
</Menu>
```
