```
initialState = {
    open: false,
};

const handleOpen = () => {
    setState({
        open: !state.open,
    });
};

<div>
    <button onClick={handleOpen}>Open Portal</button>
    <Portal open={state.open}>Lol</Portal>
</div>
```
