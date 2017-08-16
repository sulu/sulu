```
const {Option, Divider} = require('../Select');
initialState = {contributors: []};
const onChange = (contributors) => setState({contributors});

<MultiSelect values={state.contributors} label="Choose contributors" onChange={onChange}>
    <Option value="page-1">Linus Torvald</Option>
    <Option value="page-2">Dennis Ritchie</Option>
    <Option value="page-3">Larry Page</Option>
    <Option value="page-4">Bill Gates</Option>
</MultiSelect>
```
