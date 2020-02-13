Chips are compact elements that represent an attribute in its used context. It can e.g. be used to display tags in a tag
selection or can indicate that a list is filtered.

```javascript
<div style={{backgroundColor: 'white', padding: '10px'}}>
    <Chip>Tag 1</Chip>
</div>
```

Chips can also be displayed in a disabled state:

```javascript
<div style={{backgroundColor: 'white', padding: '10px'}}>
    <Chip disabled={true}>Tag 2</Chip>
</div>
```

Chips can also be clicked, which will trigger the optional `onClick` callback:

```javascript
const handleClick = (value) => {
    alert('Clicked the chip with the value ' + value);
};

<div style={{backgroundColor: 'white', padding: '10px'}}>
    <Chip onClick={handleClick} value={7}>Click me!</Chip>
</div>
```

They also accept a `onDelete` callback, which will render an `Icon` the user can click:

```javascript
const handleDelete = (value) => {
    alert('Delete the chip with the value ' + value);
};

<div style={{backgroundColor: 'white', padding: '10px'}}>
    <Chip onDelete={handleDelete} value={9}>Remove me!</Chip>
</div>
```
