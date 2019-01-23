A card collection allows to handle multiple cards. Cards are added by passing them as children to the component. The
`onAdd`, `onEdit` and `onRemove` callbacks are called when the corresponding buttons are pressed.

```javascript
const onAdd = () => {
    alert('Add new item');
};

const onEdit = (id) => {
    alert('Edit item at position ' + id);
};

const onRemove = (id) => {
    alert('Remove item at position ' + id);
};

<CardCollection onAdd={onAdd} onEdit={onEdit} onRemove={onRemove}>
    <CardCollection.Card>
        <strong>Harry Potter</strong>
        <br />
        <em>Student</em>
    </CardCollection.Card>
    <CardCollection.Card>
        <strong>Albus Dumbledore</strong>
        <br />
        <em>Headmaster</em>
    </CardCollection.Card>
    <CardCollection.Card>
        <strong>Severus Snape</strong>
        <br />
        <em>Teacher</em>
    </CardCollection.Card>
    <CardCollection.Card>
        <strong>Ron Weasley</strong>
        <br />
        <em>Student</em>
    </CardCollection.Card>
    <CardCollection.Card>
        <strong>Hermione Granger</strong>
        <br />
        <em>Student</em>
    </CardCollection.Card>
</CardCollection>
```
