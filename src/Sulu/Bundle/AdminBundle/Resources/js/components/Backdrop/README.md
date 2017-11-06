The `Backdrop` component serves as a simple solution to create a backdrop for overlays.

Here is a basic example of the component. The open state of the backdrop is controlled by the `open` property.

```
intialState = {open: false};

<div>
    <button onClick={() => setState({open: true})}>Open Backdrop</button>
    <Backdrop open={!!state.open} onClick={() => setState({open: false})} />
</div>
```

This time the `visible` property is set to false, therefore the backdrop is invisible.

```
intialState = {open: false};

<div>
    <button onClick={() => setState({open: true})}>Open Backdrop</button>
    <Backdrop visible={false} open={!!state.open} onClick={() => setState({open: false})} />
</div>
```
