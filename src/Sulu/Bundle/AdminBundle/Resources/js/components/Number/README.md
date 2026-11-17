The number component can be used to get a number from the user in the same way as with the native browser input.

```javascript
const [value, setValue] = React.useState(null);

<Number value={value} onChange={setValue} />
```

Use the html5 attributes to configure the component.

```javascript
const [value, setValue] = React.useState(null);

<Number min={1} max={10} step={1} value={value} onChange={setValue} />
```

The `disabled` and `valid` props switch the field to the disabled or error state, `placeholder` is shown while the
value is empty, and `alignment` moves the text to the `left`, `center` or `right`.

```javascript
<div>
    <Number disabled={true} value={5} onChange={() => {}} />
    <Number valid={false} value={5} onChange={() => {}} />
    <Number placeholder="Enter a number" value={null} onChange={() => {}} />
    <Number alignment="right" value={5} onChange={() => {}} />
</div>
```

The `skin` prop switches to the dark skin, and the `onBlur` callback is called when the field loses focus.

```javascript
const [value, setValue] = React.useState(3);

<div style={{background: 'black', padding: '10px'}}>
    <Number skin="dark" value={value} onChange={setValue} onBlur={() => console.log('blur')} />
</div>
```
