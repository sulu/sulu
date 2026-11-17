The qr-code component can be used to get an input from the user as a qr-code.

```javascript
const [value, setValue] = React.useState('');

<QRCode value={value} onChange={setValue} />
```

The attributes will be passed to the input component.

```javascript
const [value, setValue] = React.useState('');

<QRCode icon="fa-key" type="password" placeholder="Password" value={value} onChange={setValue} />
```

The `valid` and `disabled` props are passed to the input as well.

```javascript
<div>
    <QRCode disabled={true} value="disabled" onChange={() => {}} />
    <QRCode valid={false} value="invalid" onChange={() => {}} />
</div>
```
