The input component can be used to get input from the user in the same way as with the native browser input.

```javascript
initialState = {value: '/parent'};
onChange = (newValue) => {
	setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value}</div>
    <ResourceLocator onChange={onChange} value={state.value} mode="full"/>
</div>
```

```javascript
initialState = {value: '/parent/child'};
onChange = (newValue) => {
	setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value}</div>
    <ResourceLocator onChange={onChange} value={state.value} mode="leaf"/>
</div>
```
