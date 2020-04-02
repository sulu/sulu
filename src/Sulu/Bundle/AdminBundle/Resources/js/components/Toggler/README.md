The `Toggler` is an boolean input element and has no internal state. It has to be managed from the outside,
like shown in the following example:

```javascript
const [checked1, setChecked1] = React.useState(false);
const [checked2, setChecked2] = React.useState(true);

<div>
    <Toggler value="1" checked={checked1} onChange={setChecked1}>Airplane mode</Toggler>
    <Toggler value="2" checked={checked2} onChange={setChecked2}>Night mode</Toggler>
</div>
```
