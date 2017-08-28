The multi select component allows the user to select many options out of many.
The component follows the
[recommendation of React for form components](https://facebook.github.io/react/docs/forms.html):
The component itself holds no internal state and is solely dependent on the passed properties.
Moreover, it provides a possibility to pass a callback which gets called when the user changes an option.

```
const Action = require('../Action').default;
const Divider = require('../Divider').default;
const Option = require('../Option').default;

initialState = {contributors: []};
const onChange = (contributors) => setState({contributors});

<MultiSelect
    values={state.contributors}
    noneSelectedText="Choose contributors"
    allSelectedText="All"
    onChange={onChange}>
    <Option value="page-1">Linus Torvald</Option>
    <Option value="page-2">Dennis Ritchie</Option>
    <Option value="page-3">Larry Page</Option>
    <Option value="page-4">Bill Gates</Option>
    <Divider />
    <Action onClick={() => {/* do something */}}>Add new contributor</Action>
</MultiSelect>
```
