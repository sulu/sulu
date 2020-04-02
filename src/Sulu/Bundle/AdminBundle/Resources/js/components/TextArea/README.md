A simple textarea component. The `onChange` callback is called whenever a change happens and the `onFinish` callback is
called as soon as the component loses its focus.

```javascript
const [value, setValue] = React.useState('');

<TextArea
    value={value}
    placeholder="Tell me something about yourself..."
    onChange={setValue}
    onFinish={() => alert('TextArea lost focus!')}
/>
```

It is also possible to pass a `maxCharacters` prop to show a [`CharacterCounter`](#charactercounter).

```javascript
const [value, setValue] = React.useState('');

<TextArea
    maxCharacters={10}
    onChange={setValue}
    value={value}
/>
```
