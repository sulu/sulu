The modal component let's you display some content above everything else.
It renders depending on the passed property and request being closed through a callback.

```
initialState = {open: false};
const actions = [
    {title: 'Destroy world', onClick: () => {/* destroy world */}},
    {title: 'Save world', onClick: () => {/* save world */}},
];
const onConfirm = () => {
    /* do confirm things */
    setState({open: false});
};

<div>
    <button onClick={() => setState({open: true})}>Open modal</button>
    <Modal
        title="Njan Njan Njan"
        onRequestClose={() => setState({open: false})}
        actions={actions}
        confirmText="Apply"
        onConfirm={onConfirm}
        isOpen={state.open}>
        <div style={{width: '900px', height: '500px', display: 'flex', alignItems: 'center', justifyContent: 'center'}}>
            <img src="http://www.nyan.cat/cats/original.gif" />
        </div>
    </Modal>
</div>
```

If you don't want to handle the open/close state yourself and want to just open the modal
when a specific element is clicked, `ClickModal` is the right choice.
It renders the element which triggers the modal on click and handles the whole opening and closing
of the modal internally.

```
const ClickModal = require('./ClickModal').default;
const actions = [
    {title: 'Save Gotham', onClick: () => {/* save gotham */}},
];
const onConfirm = () => {
    /* do confirm things */
};

const button = (<button>Open modal</button>);
<ClickModal
    clickElement={button}
    title="Nana Nana Nana"
    actions={actions}
    onConfirm={onConfirm}
    confirmText="Ok">
    <div style={{width: '900px', height: '500px', display: 'flex', alignItems: 'center', justifyContent: 'center'}}>
        <img src="https://media.giphy.com/media/NmhVw98IHkQtq/source.gif" />
    </div>
</ClickModal>
```
