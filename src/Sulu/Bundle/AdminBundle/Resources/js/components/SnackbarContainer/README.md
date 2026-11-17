The `SnackbarContainer` component is used to display floating snackbar messages.
It is fixed and uses a React.Portal so the messages are at to top of every other elements.

The `className` prop is added to the container, the children are the snackbars to display.

```javascript
const Snackbar = require('../Snackbar').default;

<SnackbarContainer>
    <Snackbar skin="floating" message="First message" type="info" />
    <Snackbar skin="floating" message="Second message" type="success" />
</SnackbarContainer>
```
