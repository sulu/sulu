The `SnackbarContainer` component is used to display floating snackbar messages.
It is fixed and uses a React.Portal so the messages are at to top of every other elements.

The `className` prop is added to the container, the children are the snackbars to display. The messages are only
rendered while the example is toggled on, because the container covers the whole page.

```javascript
const Snackbar = require('../Snackbar').default;
const [visible, setVisible] = React.useState(false);

<div>
    <button onClick={() => setVisible(!visible)}>{visible ? 'Hide messages' : 'Show messages'}</button>
    {visible && (
        <SnackbarContainer>
            <Snackbar skin="floating" message="First message" type="info" onCloseClick={() => setVisible(false)} />
            <Snackbar skin="floating" message="Second message" type="success" onCloseClick={() => setVisible(false)} />
        </SnackbarContainer>
    )}
</div>
```
