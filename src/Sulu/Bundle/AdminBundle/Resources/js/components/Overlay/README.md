The `Overlay` component let's you display some content above everything else.
It renders depending on the passed property and request being closed through a callback.

```javascript
const [open, setOpen] = React.useState(false);
const actions = [
    {title: 'Destroy world', onClick: () => {/* destroy world */}},
    {title: 'Save world', onClick: () => {/* save world */}},
];
const onConfirm = () => {
    /* do confirm things */
    setOpen(false);
};

<div>
    <button onClick={() => setOpen(true)}>Open overlay</button>
    <Overlay
        title="Njan Njan Njan"
        onClose={() => setOpen(false)}
        actions={actions}
        confirmText="Apply"
        onConfirm={onConfirm}
        open={open}>
        <div style={{width: '900px', height: '500px', display: 'flex', alignItems: 'center', justifyContent: 'center'}}>
            <img src="http://www.nyan.cat/cats/original.gif" />
        </div>
    </Overlay>
</div>
```

Errors and warnings can be displayed at the top of the overlay.

```javascript
const [open, setOpen] = React.useState(false);
const [snackbarType, setSnackbarType] = React.useState(undefined);
const onConfirm = () => {
    /* do confirm things */
    setOpen(false);
};

const snackbarMessage = snackbarType === 'error'
    ? 'An error occurred'
    : snackbarType === 'warning'
        ? 'Something strange happened'
        : undefined;

<div>
    <button onClick={() => setOpen(true)}>Open overlay</button>
    <Overlay
        title="Njan Njan Njan"
        onClose={() => setOpen(false)}
        confirmText="Apply"
        size="large"
        onConfirm={onConfirm}
        open={open}
        snackbarType={snackbarType}
        snackbarMessage={snackbarMessage}>
        <div style={{padding: '50px', display: 'flex', alignItems: 'center', justifyContent: 'center'}}>
            <button onClick={() => setSnackbarType((type) => type !== 'error' ? 'error' : undefined)}>Toggle error</button>
            &nbsp;
            <button onClick={() => setSnackbarType((type) => type !== 'warning' ? 'warning' : undefined)}>Toggle warning</button>
        </div>
    </Overlay>
</div>
```
