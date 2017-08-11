The select component can be used to ask the user to select one out of many options.
The component follows the recommendation of React for form components: The Select itself
holds no internal state and is solely dependent on the passed properties. Moreover, it provides
a possibility to pass a callback which gets called when the user selects an option.
```
const Divider = require('./Divider').default;
const Option = require('./Option').default;

initialState = {selectValue: null};
const onChange = (value) => value !== 'action-create' ? setState({selectValue: value}) : false;

<Select value={state.selectValue} onChange={onChange}>
    <Option value="page-1">Page 1 of 4</Option>
    <Option value="page-2">Page 2 of 4</Option>
    <Option value="page-3">Page 3 of 4</Option>
    <Option value="page-4">Page 4 of 4</Option>
    <Divider />
    <Option value="action-create">Create new page</Option>
</Select>
```

Also a lot of options are possible and correctly handled as well as neatly styled.
```
const Divider = require('./Divider').default;
const Option = require('./Option').default;

initialState = {selectValue: null};
const onChange = (value) => setState({selectValue: value});

<Select value={state.selectValue} onChange={onChange}>
    <Option disabled>Choose the owner</Option>
    <Option value="1">Donald Duck</Option>
    <Option value="2">Mickey Mouse</Option>
    <Option value="3">Dagobert Duck</Option>
    <Option value="4">Tick Duck</Option>
    <Option value="5">Trick Duck</Option>
    <Option value="6">Track Duck</Option>
    <Option value="7">Minney Mouse</Option>
    <Option value="8">Goofey</Option>
    <Option value="9">Superman</Option>
    <Option value="10">Batman</Option>
    <Option value="11">Harry Potter</Option>
    <Option value="12">Lilly Potter</Option>
    <Option value="13">James Potter</Option>
    <Option value="14">Albus Dumbledore</Option>
    <Option value="15">Severus Snape</Option>
    <Option value="16">Ron Weasly</Option>
    <Option value="17">Hermoine Granger</Option>
    <Option value="18">Tom Riddle</Option>
    <Option value="19">Bathilda Bagshot</Option>
    <Option value="20">Susan Bones</Option>
    <Option value="21">Marvolo Gaunt</Option>
    <Option value="22">Godric Gryffindor</Option>
</Select>
```

Because the select doesn't change its label itself, its fairly straight forward to provide a select
for which the lable never changes.
```
const Divider = require('./Divider').default;
const Option = require('./Option').default;

const onChange = (action) => {/* do something */};

<Select icon="plus" onChange={onChange}>
    <Option disabled>Add something</Option>
    <Option value="action-page">Add a page</Option>
    <Option value="action-person">Add a person</Option>
    <Option value="action-image">Add an image</Option>
    <Option value="action-salt">Add salt</Option>
</Select>
```
