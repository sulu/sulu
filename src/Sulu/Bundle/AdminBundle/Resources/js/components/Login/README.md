The `Login` component consists of a form in which the user can enter his or her authentication details and a background image which covers the whole background of the page. The form itself offers two different views. One is the default login-view and the other serves as a reset-password-view.

```javascript
const Translator = require('../../utils/Translator');
Translator.setTranslations({
    "sulu_admin.error_minitems": "This field has too few assignments",
    "sulu_admin.back_to_website": "Back to website",
    "sulu_admin.username_or_email": "Username or Email",
    "sulu_admin.password": "Password",
    "sulu_admin.login": "Login",
    "sulu_admin.to_login": "Back to login",
    "sulu_admin.reset": "Reset",
    "sulu_admin.forgot_password": "Forgot password"
});

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
