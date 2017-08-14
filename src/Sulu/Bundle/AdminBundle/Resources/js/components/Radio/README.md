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

```
const RadioGroup = require('./RadioGroup').default;

initialState = {value: '1'};

<RadioGroup value={state.value} onChange={(value) => setState({value})}>
    <Radio value="1" />
    <Radio value="2" />
    <Radio value="3" />
</RadioGroup>
```
