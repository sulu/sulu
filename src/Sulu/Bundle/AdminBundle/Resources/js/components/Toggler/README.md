The toggler is an boolean input element and has no internal state. It has to be managed from the outside,
like shown in the following example:

```
initialState = {checked: false};
onChange = (checked) => setState({checked});
<Toggler checked={state.checked} onChange={onChange} />
```
