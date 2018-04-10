The PasswordConfirmation component can be used to type in a password using two usual password field. The `onChange`
callback will only be called if both password fields have the same value.

```
const onChange = (newValue) => {
    alert('Both passwords are identical now!');
};

<PasswordConfirmation onChange={onChange} />
```
