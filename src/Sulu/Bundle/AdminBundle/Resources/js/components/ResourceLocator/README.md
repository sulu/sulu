The ResourceLocator component can be used to get a URL from user input in two modes.

* `full`: User can change everything after the initial slash.
* `leaf`: User just can change the part after the last slash.

```javascript
initialState = {value: '/parent'};
const onChange = (newValue) => {
	setState({value: newValue});
};
``
<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value}</div>
    <ResourceLocator onChange={onChange} value={state.value} mode="full"/>
</div>
```

```javascript
initialState = {value: '/parent/child'};
const onChange = (newValue) => {
	setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value}</div>
    <ResourceLocator onChange={onChange} value={state.value} mode="leaf"/>
</div>
```
