The number component can be used to get a number from the user in the same way as with the native browser input.

```javascript
initialState = {value: null};
const onChange = (newValue) => {
    setState({value: newValue});
};

<Number value={state.value} onChange={onChange} />
```

Use the html5 attributes to configure the component.

```javascript
initialState = {value: null};
const onChange = (newValue) => {
    setState({value: newValue});
};

<Number min={1} max={10} step={1} value={state.value} onChange={onChange} />
```
