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

The `footer` prop replaces the default footer with own content, e.g. to align several buttons on the right.

```javascript
const [open, setOpen] = React.useState(false);

const footer = (
    <div style={{marginLeft: 'auto', display: 'flex', gap: '12px'}}>
        <button onClick={() => setOpen(false)}>Reject</button>
        <button onClick={() => setOpen(false)}>Approve</button>
    </div>
);

<div>
    <button onClick={() => setOpen(true)}>Open overlay</button>
    <Overlay footer={footer} onClose={() => setOpen(false)} open={open} size="small" title="Review">
        <div style={{padding: '50px'}}>Decide on this content.</div>
    </Overlay>
</div>
```

The `size` prop is `small` or `large`. With `closable={false}` the close button is hidden, `confirmDisabled` disables
and `confirmLoading` shows a spinner on the confirm button. A `snackbarMessage` of a `snackbarType` (`error`,
`warning`, `info`, `success`) is shown on top, with `onSnackbarClick` and `onSnackbarCloseClick` as callbacks.

```javascript
const [open, setOpen] = React.useState(false);
const [loading, setLoading] = React.useState(false);

<div>
    <button onClick={() => setOpen(true)}>Open overlay</button>
    <Overlay
        closable={false}
        confirmDisabled={false}
        confirmLoading={loading}
        confirmText="Save"
        onClose={() => setOpen(false)}
        onConfirm={() => {
            setLoading(true);
            setTimeout(() => {setLoading(false); setOpen(false);}, 1000);
        }}
        onSnackbarClick={() => console.log('snackbar clicked')}
        onSnackbarCloseClick={() => console.log('snackbar closed')}
        open={open}
        size="small"
        snackbarMessage="Something needs your attention"
        snackbarType="warning"
        title="Small overlay"
    >
        <div style={{padding: '20px'}}>Content of a small, non closable overlay.</div>
    </Overlay>
</div>
```
