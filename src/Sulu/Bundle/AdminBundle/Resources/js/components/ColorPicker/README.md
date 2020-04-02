The ColorPicker component can be used to get a valid color from the user.
The picker can be opened via clicking on the icon.

```javascript
const [value, setValue] = React.useState(undefined);

const onChange = (value) => setValue(value);

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value : 'null'}</div>
    <ColorPicker value={value} onChange={onChange} />
</div>
```
