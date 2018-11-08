Radio buttons keep no internal state and have to be managed from the outside, like shown in the
following example:

```javascript
initialState = {value: '1'};
<div>
    <Radio checked={state.value === '1'} onChange={() => setState({value: '1'})}>Radio 1</Radio>
    <Radio checked={state.value === '2'} onChange={() => setState({value: '2'})}>Radio 2</Radio>
    <Radio checked={state.value === '3'} onChange={() => setState({value: '3'})}>Radio 3</Radio>
</div>
```

Radio buttons come with two different styles which can be set through the `skin` prop. The default skin is "dark".
The "light" style looks like as in the following example:

```javascript
<div style={{ backgroundColor: '#888', padding: '10px' }}>
    <Radio skin="light" checked={state.value === '1'} onChange={() => setState({value: '1'})}>Radio 1</Radio>
    <Radio skin="light" checked={state.value === '2'} onChange={() => setState({value: '2'})}>Radio 2</Radio>
    <Radio skin="light" checked={state.value === '3'} onChange={() => setState({value: '3'})}>Radio 3</Radio>
</div>
```

In most cases the state management of the radio buttons will be the same.
For that matter the `RadioGroup` component makes the use of the radio buttons more convenient.

```javascript
const RadioGroup = require('./RadioGroup').default;

initialState = {value: '1'};

<RadioGroup value={state.value} onChange={(value) => setState({value})}>
    <Radio value="1">Radio 1</Radio>
    <Radio value="2">Radio 2</Radio>
    <Radio value="3">Radio 3</Radio>
</RadioGroup>
```
