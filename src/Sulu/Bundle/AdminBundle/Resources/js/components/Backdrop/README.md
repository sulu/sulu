The Backdrop component serves as a simple solution to create a backdrop for modals and other kinds of overlays.

Here is a basic example of the component. The open state of the backdrop is controlled by the `isOpen` property.

```
intialState = {open: false};

<div>
    <button onClick={() => setState({open: true})}>Open Backdrop</button>
    <Backdrop isOpen={!!state.open} onClick={() => setState({open: false})} />
</div>
```

This time the `isVisible` property is set to false, therefore the backdrop is invisible.

```
intialState = {open: false};

<div>
    <button onClick={() => setState({open: true})}>Open Backdrop</button>
    <Backdrop isVisible={false} isOpen={!!state.open} onClick={() => setState({open: false})} />
</div>
```
