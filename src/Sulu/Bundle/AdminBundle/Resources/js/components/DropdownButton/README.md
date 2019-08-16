The `DropdownButton` component is a button that opens a dropdown with multiple options.

```javascript
const handleOption1Click = () => {
    alert('Option1 has been chosen');
};

const handleOption2Click = () => {
    alert('Option2 has been chosen');
};

<DropdownButton icon="su-plus" label="Button">
    <DropdownButton.Item onClick={handleOption1Click}>Option 1</DropdownButton.Item>
    <DropdownButton.Item onClick={handleOption2Click}>Option 2</DropdownButton.Item>
</DropdownButton>
```

The `DropdownButton` has the same skins available as the [Button component](#button).

```javascript
const handleOption1Click = () => {
    alert('Option1 has been chosen');
};

const handleOption2Click = () => {
    alert('Option2 has been chosen');
};

<DropdownButton icon="su-plus" label="Button" skin="primary">
    <DropdownButton.Item onClick={handleOption1Click}>Option 1</DropdownButton.Item>
    <DropdownButton.Item onClick={handleOption2Click}>Option 2</DropdownButton.Item>
</DropdownButton>
```

```javascript
const handleOption1Click = () => {
    alert('Option1 has been chosen');
};

const handleOption2Click = () => {
    alert('Option2 has been chosen');
};

<DropdownButton icon="su-plus" skin="icon">
    <DropdownButton.Item onClick={handleOption1Click}>Option 1</DropdownButton.Item>
    <DropdownButton.Item onClick={handleOption2Click}>Option 2</DropdownButton.Item>
</DropdownButton>
```
