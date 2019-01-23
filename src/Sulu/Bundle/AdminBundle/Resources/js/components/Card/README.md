Shows a card component, which is a styled rectangle, which can be filled with any content.

```javascript
<Card>
    <h2>Any content!</h2>
</Card>
```

It is possible to add `onEdit` and `onRemove` callbacks. These are called when the corresponding button will be pressed.

```javascript
const onEdit = () => alert('Edit callback executed');
const onRemove = () => alert('Remove callback executed');

<Card onEdit={onEdit} onRemove={onRemove}>
    Editable and deletable content
</Card>
```
