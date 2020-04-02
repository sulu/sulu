The email component can be used to get a valid email from the user.

```javascript
const [value, setValue] = React.useState(undefined);

const onChange = (value) => setValue(value);

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value : 'null'}</div>
    <Email value={value} onChange={onChange} />
</div>
```
