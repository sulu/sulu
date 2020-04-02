The phone component can be used to get a phone number from the user.

```javascript
const [value, setValue] = React.useState(undefined);

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value : 'null'}</div>
    <Phone value={value} onChange={setValue} />
</div>
```
