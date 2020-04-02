The custom checkbox has no internal state and has to be managed, like shown in the following example.
The change callback receives the value as an optional second parameter.

```javascript
const [checked1, setChecked1] = React.useState(false);
const [checked2, setChecked2] = React.useState(true);

<div>
    <Checkbox value="1" checked={checked1} onChange={setChecked1}>Save the world</Checkbox>
    <Checkbox value="2" checked={checked2} onChange={setChecked2}>Buy groceries</Checkbox>
</div>
```

The checkbox also comes with a light skin and active attribute.

```javascript
const [checked, setChecked] = React.useState(false);

const onChange = (checked) => setChecked(checked);
<div style={{background: 'black', padding: '10px'}}>
    <Checkbox skin="light" checked={checked} onChange={onChange} />
</div>
```

```javascript
const [checked, setChecked] = React.useState(false);

const onChange = (checked) => setChecked(checked);
<div style={{background: 'black', padding: '10px'}}>
    <Checkbox active={false} checked={checked} onChange={onChange} />
</div>
```
