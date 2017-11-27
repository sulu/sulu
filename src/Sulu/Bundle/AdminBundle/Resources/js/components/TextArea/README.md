A simple textarea component.

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
/>
```
