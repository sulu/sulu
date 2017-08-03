The modal component let's you display some content above everything else.
It renders depending on the passed property and request being closed through a callback.

```
initialState = {open: false};

<div>
    <button onClick={() => setState({open: true})}>Open modal</button>
    <Modal onRequestClose={() => setState({open: false})} isOpen={state.open}>
        <div style={{width: '500px', height: '500px'}}>My modal content</div>
    </Modal>
</div>
```

If you don't want to handle the open/close state yourself and want to just open the modal
when a specific element is clicked, `ClickModal` is the right choice.
It renders the element which triggers the modal on click and handles the whole opening and closing
of the modal internally.

```
const ClickModal = require('./ClickModal').default;

const button = (<button>Open modal</button>);
<ClickModal clickElement={button}>
    <div style={{width: '500px', height: '1000px'}}>My modal content</div>
</ClickModal>
```
