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

<Input icon="fa-key" type="password" placeholder="Password" value={state.value} onChange={onChange} />
```

When setting the `valid` prop to `false` it will mark the field as invalid. The following example shows an input field
that needs to contain some text.

```javascript
initialState = {valid: false, error: {}};
const onChange = (newValue) => {
    let error = undefined;
    if (newValue.length === 0) {
        error = {};
    }
    setState({error, value: newValue});
};

<Input error={state.error} value={state.value} onChange={onChange} />
```
