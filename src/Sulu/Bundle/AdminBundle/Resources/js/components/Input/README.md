The input component can be used to get input from the user in the same way as with the native browser input.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
	setState({value: newValue});
};

<Input value={state.value} onChange={onChange} />
```

Beneath attributes known from the native input, it provides properties to style the the input.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
	setState({value: newValue});
};

<Input icon="key" type="password" placeholder="Password" value={state.value} onChange={onChange} />
```
