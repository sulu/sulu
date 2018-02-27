The ResourceLocator component can be used to get a URL from user input in two modes.

In the `full` mode the user can change the entire URL except for the leading slash:

```javascript
initialState = {value: '/parent'};
const onChange = (newValue) => {
	setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value}</div>
    <ResourceLocator onChange={onChange} value={state.value} mode="full"/>
</div>
```

In the `leaf` mode the user is only capable of editing the part after the last slash:

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

The ResourceLocator also calls its `onFinish` callback when the input loses focus.

```javascript
initialState = {value: '/parent'};
const onChange = (newValue) => {
	setState({value: newValue});
};

<ResourceLocator
    onChange={onChange}
    onFinish={() => alert('The ResourceLocator lost its focus')}
    value={state.value}
    mode="full"
/>
```
