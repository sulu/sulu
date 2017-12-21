The `Login` component consists of a form in which the user can enter his or her authentication details and a background image which covers the whole background of the page. The form itself offers two different views. One is the default login-view and the other serves as a reset-password-view.

```javascript
const handleLogin = (user, password) => {
    setState({
        user,
        password
    });
};

const handleResetPassword = (user) => {
    setState({user});
};

<div>
    <Login onLogin={handleLogin} onResetPassword={handleResetPassword} />
    <div>User: {state.user}</div>
    <div>Password: {state.password}</div>
</div>
```
