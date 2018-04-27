The custom checkbox has no internal state and has to be managed, like shown in the following example.
The change callback receives the value as an optional second parameter.

```
initialState = {checked1: false, checked2: true};
onChange = (checked, value) => setState({['checked' + value]: checked});
<div>
    <Checkbox value="1" checked={state.checked1} onChange={onChange}>Save the world</Checkbox>
    <Checkbox value="2" checked={state.checked2} onChange={onChange}>Buy groceries</Checkbox>
</div>
```

The checkbox also comes with a light skin and active attribute.

```
initialState = {checked: false};
onChange = (checked) => setState({checked});
<div style={{background: 'black', padding: '10px'}}>
    <Checkbox skin="light" checked={state.checked} onChange={onChange} />
</div>
```

```
initialState = {checked: false};
onChange = (checked) => setState({checked});
<div style={{background: 'black', padding: '10px'}}>
    <Checkbox active={false} checked={state.checked} onChange={onChange} />
</div>
```
