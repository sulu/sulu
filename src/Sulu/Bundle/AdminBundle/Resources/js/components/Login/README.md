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

<Login onLogin={handleLogin} onResetPassword={handleResetPassword} />
```
