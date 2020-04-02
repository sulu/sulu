This component accepts nothing but a valid [IBAN](https://www.iso.org/standard/41031.html). It returns `undefined` and
will be marked by a red border if the given string is not a valid IBAN. It uses an [`Input` component](#input)
underneath, and also exposes the props from the `Input` making sense for this use case.

```javascript
const [value, setValue] = React.useState(undefined);

const onChange = (value) => setValue(value);

<div>
    <Iban onChange={onChange} value={value} />
    Value: {value}
</div>
```
