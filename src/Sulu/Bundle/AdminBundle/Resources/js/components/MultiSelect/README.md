The `MultiSelectComponent` allows the user to select many options out of many.
The component follows the
[recommendation of React for form components](https://facebook.github.io/react/docs/forms.html):
The component itself holds no internal state and is solely dependent on the passed properties.
Moreover, it provides a possibility to pass a callback which gets called when the user changes an option.

```javascript
initialState = {contributors: []};
const onChange = (contributors) => setState({contributors});

<div style={{maxWidth: '200px'}}>
    <MultiSelect
        values={state.contributors}
        noneSelectedText="Choose contributors"
        allSelectedText="All"
        onChange={onChange}
    >
        <MultiSelect.Option value="page-1">Linus Torvald</MultiSelect.Option>
        <MultiSelect.Option value="page-2">Dennis Ritchie</MultiSelect.Option>
        <MultiSelect.Option value="page-3">Larry Page</MultiSelect.Option>
        <MultiSelect.Option value="page-4">Bill Gates</MultiSelect.Option>
        <MultiSelect.Divider />
        <MultiSelect.Action onClick={() => {/* do something */}}>Add new contributor</MultiSelect.Action>
    </MultiSelect>
</div>
```
