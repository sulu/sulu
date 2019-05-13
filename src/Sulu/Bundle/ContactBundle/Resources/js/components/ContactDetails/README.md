This component allows to assign different kind of contact details. This includes phone number, email addresses,
websites, faxes and social media profiles.

```javascript
initialState = {
    value: {
        emails: [{email: undefined}],
        faxes: [{fax: undefined}],
        phones: [{phone: undefined}],
        socialMedia: [{username: undefined}],
        websites: [{website: undefined}],
    },
};

const changeHandler = (value) => {
    setState({value});
};

<ContactDetails onChange={changeHandler} value={state.value} />
```
