The `Toggler` is an boolean input element and has no internal state. It has to be managed from the outside,
like shown in the following example:

```javascript
initialState = {checked1: false, checked2: true};
onChange = (checked, value) => setState({['checked' + value]: checked});
<div>
    <Toggler value="1" checked={state.checked1} onChange={onChange}>Airplane mode</Toggler>
    <Toggler value="2" checked={state.checked2} onChange={onChange}>Night mode</Toggler>
</div>
```
