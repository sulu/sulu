The email component can be used to get a valid email from the user.

```javascript
initialState = {value: undefined};
const onChange = (newValue) => {
    setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value : 'null'}</div>
    <Email value={state.value} onChange={onChange} />
</div>
```