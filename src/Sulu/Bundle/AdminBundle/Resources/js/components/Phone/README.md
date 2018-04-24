The phone component can be used to get phone from the user in the same way as with the native browser input tel.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
    setState({value: newValue});
};

<Phone value={state.value} onChange={onChange} />
```