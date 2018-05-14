The ColorPicker component can be used to get a valid color from the user.
The picker can be opened via clicking on the icon.

```javascript
initialState = {value: undefined};
const onChange = (newValue) => {
    setState({value: newValue});
};

<div>
    <div style={{paddingBottom: '50px'}}>Current value: {state.value ? state.value : 'null'}</div>
    <ColorPicker value={state.value} onChange={onChange} />
</div>
```