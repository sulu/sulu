The email component can be used to get a valid email from the user.

```javascript
const [value, setValue] = React.useState(undefined);

const onChange = (value) => setValue(value);

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {value ? value : 'null'}</div>
    <Email value={value} onChange={onChange} />
</div>
```

The `placeholder`, `disabled` and `valid` props behave like in the [`Input`](#input). The icon opens the mail client.

```javascript
<div>
    <div style={{paddingBottom: '10px'}}>
        <Email onChange={() => {}} placeholder="Enter an email" value={undefined} />
    </div>
    <div style={{paddingBottom: '10px'}}>
        <Email disabled={true} onChange={() => {}} value="info@example.org" />
    </div>
    <Email onChange={() => {}} valid={false} value="not-an-email" />
</div>
```
