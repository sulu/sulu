This component allows to assign different kind of contact details. This includes phone number, email addresses,
websites, faxes and social media profiles.

```javascript
const [value, setValue] = React.useState({
    emails: [{email: undefined, emailType: 1}],
    faxes: [{fax: undefined, faxType: 1}],
    phones: [{phone: undefined, phoneType: 1}],
    socialMedia: [{username: undefined, socialMediaType: 1}],
    websites: [{website: undefined, websiteType: 1}],
});

const changeHandler = (value) => {
    setValue(value);
};

<ContactDetails onChange={changeHandler} value={value} />
```

The `onBlur` prop is called when one of the inputs loses focus. The `value` consists of the `emails`, `faxes`, `phones`,
`socialMedia` and `websites` arrays, each entry holding the actual value and its type id. Existing entries are shown
with their type selects and can be removed:

```javascript
const [value, setValue] = React.useState({
    emails: [{email: 'hello@sulu.io', emailType: 1}],
    faxes: [],
    phones: [{phone: '+43 123 456', phoneType: 1}, {phone: '+43 789 012', phoneType: 2}],
    socialMedia: [{username: 'sulu', socialMediaType: 1}],
    websites: [{website: 'https://sulu.io', websiteType: 1}],
});

<ContactDetails onBlur={() => {}} onChange={setValue} value={value} />
```
