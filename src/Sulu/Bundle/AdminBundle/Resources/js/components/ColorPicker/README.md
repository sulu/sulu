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

The `placeholder`, `disabled` and `valid` props behave like in the [`Input`](#input). `valid={false}` marks the field as
erroneous.

```javascript
<div>
    <div style={{paddingBottom: '10px'}}>
        <ColorPicker onChange={() => {}} placeholder="Choose a color" value={undefined} />
    </div>
    <div style={{paddingBottom: '10px'}}>
        <ColorPicker disabled={true} onChange={() => {}} value="#ff0000" />
    </div>
    <ColorPicker onChange={() => {}} valid={false} value="#00ff00" />
</div>
```
