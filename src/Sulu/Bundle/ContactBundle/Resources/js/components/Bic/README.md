This component accepts nothing but a valid [BIC](https://www.iso9362.org/). It returns `undefined` and will be marked by
a red border if the given string is not a valid BIC. It uses an [`Input` component](#input) underneath, and also exposes
the props from the `Input` making sense for this use case.

```javascript
const [value, setValue] = React.useState(undefined);

const onChange = (value) => setValue(value);

<div>
    <Bic onChange={onChange} value={value} />
    Value: {value}
</div>
```
