The PasswordConfirmation component can be used to type in a password using two usual password field. The `onChange`
callback will only be called if both password fields have the same value. Validation and calling the callback happens
in a debounced manner, which means that it takes some time until their effects appear.

```
const onChange = (newValue) => {
    alert('Both passwords are identical now!');
};

<PasswordConfirmation onChange={onChange} />
```

The `disabled` prop disables both fields, and `valid={false}` marks them as erroneous.

```javascript
<div>
    <PasswordConfirmation disabled={true} onChange={() => {}} />
    <PasswordConfirmation valid={false} onChange={() => {}} />
</div>
```
