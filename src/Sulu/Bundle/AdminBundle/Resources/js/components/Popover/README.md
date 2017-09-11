The Popover component is a simple element which can contain other kinds of components. A good usecase example 
is the dropdown when a select is opened or the result list for the Autocomplete component. The Popover component
always expects to receive an anchor element as a prop, which can be any kind of HTML element.

```
const Option = require('../Select').Option;

initialState = {
    open: true,
    anchorEl: null,
};

const handleClose = () => {
    setState({
        open: false,
    });
};

const handleOpen = (event) => {
    setState({
        open: true,
        anchorEl: event.currentTarget,
    });
};

<div>
    <button onClick={handleOpen}>Pop me over bae!</button>
    <Popover
        open={state.open}
        anchorEl={state.anchorEl}
        onClose={handleClose}>
        <ul style={{listStyle: 'none', margin: 0, padding: 0,}}>
            <Option>Cornelius</Option>
            <Option>Handsome</Option>
            <Option>Jakob</Option>
        </ul>
    </Popover>
</div>
```
