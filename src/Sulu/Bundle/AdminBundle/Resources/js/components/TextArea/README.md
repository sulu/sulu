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

The `rows` prop sets the visible height, `disabled` and `valid={false}` show the disabled and error state, and
`onBlur` and `onFocus` are called when the focus changes.

```javascript
<div>
    <TextArea rows={5} value="Five rows high" onChange={() => {}} onBlur={() => console.log('blur')} onFocus={() => console.log('focus')} />
    <TextArea disabled={true} value="Disabled" onChange={() => {}} />
    <TextArea valid={false} value="Invalid" onChange={() => {}} />
</div>
```
