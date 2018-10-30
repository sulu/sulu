The `SingleSelect` component can be used to ask the user to select one out of many options.
The component follows the
[recommendation of React for form components](https://facebook.github.io/react/docs/forms.html):
The `SingleSelect` itself holds no internal state and is solely dependent on the passed properties. Moreover, it
provides a possibility to pass a callback which gets called when the user selects an option.

```javascript
const Action = SingleSelect.Action;
const Option = SingleSelect.Option;
const Divider = SingleSelect.Divider;

initialState = {selectValue: 'page-1'};
const onChange = (selectValue) => setState({selectValue});

<SingleSelect value={state.selectValue} onChange={onChange}>
    <Option value="page-1">Page 1 of 4</Option>
    <Option value="page-2">Page 2 of 4</Option>
    <Option value="page-3">Page 3 of 4</Option>
    <Option value="page-4">Page 4 of 4</Option>
    <Divider />
    <Action onClick={() => {/* do stuff */}}>Create new page</Action>
</SingleSelect>
```

Also a lot of options are possible and correctly handled as well as neatly styled.

```javascript
const Action = SingleSelect.Action;
const Option = SingleSelect.Option;
const Divider = SingleSelect.Divider;

initialState = {selectValue: undefined};
const onChange = (value) => setState({selectValue: value});

<SingleSelect value={state.selectValue} onChange={onChange}>
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
</SingleSelect>
```
