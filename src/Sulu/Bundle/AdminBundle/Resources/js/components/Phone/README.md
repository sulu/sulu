The phone component can be used to get a phone number from the user.

```javascript
initialState = {value: undefined};
const onChange = (newValue) => {
    setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value : 'null'}</div>
    <Phone value={state.value} onChange={onChange} />
</div>
```