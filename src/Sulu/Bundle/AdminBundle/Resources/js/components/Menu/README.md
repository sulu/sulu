The `Menu` is a component to stylize a list. Usage examples of the `Menu` are the `Options` container inside the 
different `Select` component types and as the container for the `Suggestions` of the `AutoComplete`.

Example:

```
const GenericSelect = require('../GenericSelect').default;

const Option = GenericSelect.Option;

<Menu style={{maxWidth: '200px'}}>
    <Option>Item 1</Option>
    <Option>Item 2</Option>
    <Option>Item 3</Option>
</Menu>
```
