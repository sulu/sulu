The `Select` is a general select component which is used by the standard select as well as the multi select. For the
average user using one of those is likely the better choice.

The `Select` takes `Select.Option`, `Select.Divider` and `Select.Action` children, a `displayValue` shown on the
button, `isOptionSelected` to mark options, `onSelect`, `onClose`, `closeOnSelect`, `disabled`, `icon` and a `skin`
(`default`, `flat` or `dark`). With `selectedVisualization="checkbox"` options show a checkbox instead of an icon.

```javascript
const [selected, setSelected] = React.useState(['a']);

const handleSelect = (value) => {
    setSelected(selected.includes(value) ? selected.filter((v) => v !== value) : [...selected, value]);
};

<div style={{height: '220px'}}>
    <Select
        closeOnSelect={false}
        displayValue={selected.join(', ') || 'Nothing selected'}
        isOptionSelected={(option) => selected.includes(option.props.value)}
        onClose={() => console.log('closed')}
        onSelect={handleSelect}
        selectedVisualization="checkbox"
        skin="flat"
    >
        <Select.Option value="a">Option A</Select.Option>
        <Select.Option value="b">Option B</Select.Option>
        <Select.Divider />
        <Select.Option disabled={true} value="c">Disabled option</Select.Option>
    </Select>
</div>
```

A `disabled` select cannot be opened.

```javascript
<Select disabled={true} displayValue="Disabled" isOptionSelected={() => false} onSelect={() => {}}>
    <Select.Option value="a">Option A</Select.Option>
</Select>
```
