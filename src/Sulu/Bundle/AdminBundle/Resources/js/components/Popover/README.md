The `Popover` component serves as a container to position other components on top of everything else. 
A good usecase example is the dropdown when a `Select` component is opened or the result list for the `Autocomplete` 
component. The `Popover` always expects to receive an anchor element as a prop, which can be any kind of HTML
element.

```
const Menu = require('../Menu').default;
const Option = require('../Select').default.Option;

initialState = {
    open: true,
    anchorElement: null,
};

const handleClose = () => {
    setState({
        open: false,
    });
};

const handleOpen = (event) => {
    setState({
        open: true,
        anchorElement: event.currentTarget,
    });
};

<div>
    <button onClick={handleOpen}>Pop me over bae!</button>
    <Popover
        open={state.open}
        anchorElement={state.anchorElement}
        onClose={handleClose}>
        {
            (setPopoverRef, styles) => (
                <Menu menuRef={setPopoverRef} style={styles}>
                    <Option>Cornelius</Option>
                    <Option>Handsome</Option>
                    <Option>Jakob</Option>
                </Menu>
            )
        }
    </Popover>
</div>
```
