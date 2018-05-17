The `Login` container consists of a form in which the user can enter his credentials.
The form itself offers two different views. One is the default login-view and the other serves as a reset-password-view.

The container directly communicates with the `UserStore` to send requests to the backend.
On successfully login the prop `onLoginSuccess` is called.

A loader is shown until the prop `initialized` gets `true`.

With the prop `backLink` the target of the link in the bottom "Back to website" can be set.
