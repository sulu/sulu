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
