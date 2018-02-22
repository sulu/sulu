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
