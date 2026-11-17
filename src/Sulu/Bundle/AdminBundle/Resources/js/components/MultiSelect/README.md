The `MultiSelectComponent` allows the user to select many options out of many.  The component follows the
[recommendation of React for form components](https://facebook.github.io/react/docs/forms.html):
The component itself holds no internal state and is solely dependent on the passed properties. Moreover, it provides a
possibility to pass an `onChange` callback which gets called when the user changes an option and an `onBlur` callback
that is called when the select is closed.

```javascript
const [contributors, setContributors] = React.useState([]);
const onClose = () => console.log('The overlay was closed with the selection: ' + contributors.join(', '));
const onChange = (contributors) => setContributors(contributors);

<div style={{maxWidth: '200px'}}>
    <MultiSelect
        allSelectedText="All"
        noneSelectedText="Choose contributors"
        onClose={onClose}
        onChange={onChange}
        values={contributors}
    >
        <MultiSelect.Option value="linus">Linus Torvald</MultiSelect.Option>
        <MultiSelect.Option value="dennis">Dennis Ritchie</MultiSelect.Option>
        <MultiSelect.Option value="larry">Larry Page</MultiSelect.Option>
        <MultiSelect.Option value="bill">Bill Gates</MultiSelect.Option>
        <MultiSelect.Divider />
        <MultiSelect.Action onClick={() => {/* do something */}}>Add new contributor</MultiSelect.Action>
    </MultiSelect>
</div>
```

The `skin` (`default`, `flat`, `dark`), `icon` and `disabled` props configure the button.

```javascript
const Option = MultiSelect.Option;

<div>
    <MultiSelect skin="flat" icon="su-plus" values={['1']} onChange={() => {}}>
        <Option value="1">Flat with icon</Option>
        <Option value="2">Other</Option>
    </MultiSelect>
    <MultiSelect disabled={true} values={['1']} onChange={() => {}}>
        <Option value="1">Disabled</Option>
        <Option value="2">Other</Option>
    </MultiSelect>
</div>
```
