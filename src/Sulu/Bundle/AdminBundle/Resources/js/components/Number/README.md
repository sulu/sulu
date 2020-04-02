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
