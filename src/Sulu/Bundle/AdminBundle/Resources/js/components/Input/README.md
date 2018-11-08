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
    setState({valid: !!newValue, value: newValue});
};

<Input valid={state.valid} value={state.value} onChange={onChange} />
```

In addition to that the `onBlur` callback will be executed when `Input` components loses the focus.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
    setState({value: newValue});
};

<Input value={state.value} onChange={onChange} onBlur={() => alert('Focus lost!')} />
```

The component also supports limiting the amount of characters typed into it.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
    setState({value: newValue});
};

<Input value={state.value} maxCharacters={5} onChange={onChange} />
```

It even supports limiting the amount of segments, which is super useful if a specific number of keywords should be
delimited e.g. by a comma.

```javascript
initialState = {value: ''};
const onChange = (newValue) => {
    setState({value: newValue});
};

<Input value={state.value} maxSegments={5} segmentDelimiter="," onChange={onChange} />
```
