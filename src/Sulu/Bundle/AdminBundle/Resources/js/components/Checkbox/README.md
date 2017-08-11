The custom checkbox has no internal state and has to be managed, like shown in the following example.
```
initialState = {checked: false};
onChange = (checked) => setState({checked});
<Checkbox checked={state.checked} onChange={onChange} />
```

The checkbox also comes with a light skin.
```
initialState = {checked: false};
onChange = (checked) => setState({checked});
<div style={{background: 'black', padding: '10px'}}>
    <Checkbox skin="light" checked={state.checked} onChange={onChange} />
</div>
```
