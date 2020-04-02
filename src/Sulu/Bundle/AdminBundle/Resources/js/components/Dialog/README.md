The `Dialog` component let's you display some content above everything else.
It renders depending on the passed property and request being closed through a callback.

```javascript
const [open, setOpen] = React.useState(false);

const onConfirm = () => {
    /* do confirm things */
    setOpen(false);
};

const onCancel = () => {
    /* do cancel things */
    setOpen(false);
};

<div>
    <button onClick={() => setOpen(true)}>Open dialog</button>
    <Dialog
        title="Question?"
        onCancel={onCancel}
        onConfirm={onConfirm}
        cancelText="No"
        confirmText="Yes"
        open={open}>
        You've got a question in here.
        Yes or no?
    </Dialog>
</div>
```

The `onCancel` and `cancelText` properties are optional, so that you can also use this component to show a message that
just need acknowleding.

```javascript
const [open, setOpen] = React.useState(false);

const onConfirm = () => {
    /* do confirm things */
    setOpen(false);
};

<div>
    <button onClick={() => setOpen(true)}>Open dialog</button>
    <Dialog
        title="Question?"
        onConfirm={onConfirm}
        confirmText="Yes"
        open={open}>
        You've got a question in here.
        Yes or no?
    </Dialog>
</div>
```
