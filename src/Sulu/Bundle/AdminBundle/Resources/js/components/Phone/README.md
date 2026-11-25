The phone component can be used to get a phone number from the user.

```javascript
const [value, setValue] = React.useState(undefined);

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value : 'null'}</div>
    <Phone value={value} onChange={setValue} />
</div>
```

The `disabled`, `valid` and `placeholder` props configure the field, `onBlur` is called when it loses focus. Clicking
the icon opens the `tel:` link of the current value.

```javascript
<div>
    <Phone disabled={true} value="+43 123 456" onChange={() => {}} />
    <Phone valid={false} value="not a number" onChange={() => {}} />
    <Phone placeholder="Phone number" value={null} onChange={() => {}} onBlur={() => console.log('blur')} />
</div>
```
