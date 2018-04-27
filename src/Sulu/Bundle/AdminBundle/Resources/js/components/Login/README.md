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
    "sulu_admin.forgot_password": "Forgot password",
    "sulu_admin.welcome": "Welcome",
    "sulu_admin.reset_password_success": "An email with instrucions how to reset your password has been sent to:",
});

const handleLogin = (user, password) => {
    const loginError = (user === 'test' && password === 'test') ? undefined : 'Uncool error, username or password is wrong!';
    setState({
        user,
        password,
        loginError,
    });
};

const handleResetPassword = (user) => {
    const resetError = (user === 'test') ? undefined : 'Uncool error, username or password is wrong!';
    const resetSuccess = resetError ? undefined : 'test@test.com';
    setState({
        resetError,
        resetSuccess,
    });
};

const handleClearError = () => {
    setState({
        loginError: undefined,
        resetError: undefined,
    });
}

<div>
    <Login
        onLogin={handleLogin}
        onResetPassword={handleResetPassword}
        onClearError={handleClearError}
        loginError={state.loginError}
        resetError={state.resetError}
        resetSuccess={state.resetSuccess}
    />
    <div>User: {state.user}</div>
    <div>Password: {state.password}</div>
</div>
```
