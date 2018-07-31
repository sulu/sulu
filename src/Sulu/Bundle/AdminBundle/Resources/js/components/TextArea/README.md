A simple textarea component. The `onChange` callback is called whenever a change happens and the `onFinish` callback is
called as soon as the component loses its focus.

```javascript
initialState = {
    value: '',
};

const handleChange = (value) => {
    setState({
        value
    });
};

<TextArea
    value={state.value}
    placeholder="Tell me something about yourself..."
    onChange={handleChange}
    onFinish={() => alert('TextArea lost focus!')}
/>
```

It is also possible to pass a `maxCharacters` prop to show a [`CharacterCounter`](#charactercounter).

```javascript
initialState = {
    value: '',
};

const handleChange = (value) => {
    setState({
        value
    });
};

<TextArea
    maxCharacters={10}
    onChange={handleChange}
    value={state.value}
/>
```
