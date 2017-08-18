Radio buttons keep no internal state and have to be managed from the outside, like shown in the
following example:

```
initialState = {value: '1'};
<div>
    <Radio checked={state.value === '1'} onChange={() => setState({value: '1'})} />
    <Radio checked={state.value === '2'} onChange={() => setState({value: '2'})} />
    <Radio checked={state.value === '3'} onChange={() => setState({value: '3'})} />
</div>
```

In most cases the state management of the radio buttons will be the same.
For that matter the `RadioGroup` component makes the use of the radio buttons more convenient.
```
const RadioGroup = require('./RadioGroup').default;

initialState = {value: '1'};

<RadioGroup value={state.value} onChange={(value) => setState({value})}>
    <Radio value="1" />
    <Radio value="2" />
    <Radio value="3" />
</RadioGroup>
```
