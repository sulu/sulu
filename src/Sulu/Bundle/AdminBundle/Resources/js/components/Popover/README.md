```
const Option = require('../Select').Option;

initialState = {
    open: false,
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
    });

    setState({
        anchorEl: event.currentTarget,
    });
};

<div>
    <button onClick={handleOpen}>Open</button>
    <Popover
        open={state.open}
        anchorEl={state.anchorEl}
        onClose={handleClose}>
        <ul style={{listStyle: 'none', margin: 0, padding: 0, backgroundColor: '#e5e5e5'}}>
            <Option>Hallo sdlslkd slkjsfkljskjsakljfkasjdsjadkljalsdjljdajsdj</Option>
            <Option>Hallo</Option>
            <Option>Hallo</Option>
            <Option>Hallo</Option>
        </ul>
    </Popover>
</div>
```
