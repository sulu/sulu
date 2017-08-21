The modal component let's you display some content above everything else.
It renders depending on the passed property and request being closed through a callback.

```
initialState = {open: false};
const actions = [
    {title: 'Destroy world', handleAction: () => {/* destroy world */}},
    {title: 'Save world', handleAction: () => {/* save world */}},
];

<div>
    <button onClick={() => setState({open: true})}>Open modal</button>
    <Modal
        title="Njan Njan Njan"
        onRequestClose={() => setState({open: false})}
        actions={actions}
        confirmText="Apply"
        isOpen={state.open} >
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
    {title: 'Save Gotham', handleAction: () => {/* save gotham */}},
];

const button = (<button>Open modal</button>);
<ClickModal
    clickElement={button}
    title="Nana Nana Nana"
    actions={actions}
    confirmText="Ok" >
    <div style={{width: '900px', height: '500px', display: 'flex', alignItems: 'center', justifyContent: 'center'}}>
        <img src="https://media.giphy.com/media/NmhVw98IHkQtq/source.gif" />
    </div>
</ClickModal>
```
