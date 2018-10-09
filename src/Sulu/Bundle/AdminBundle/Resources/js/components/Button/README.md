The `Button` component displays a button.

```javascript
const onClick = () => {
    /* do click things */
    alert('Clicked this nice button, congrats!');
};

<Button
    skin="primary"
    onClick={onClick}>
    Click me dude
</Button>
```

```javascript
const onClick = () => {
    /* do click things */
    alert('Clicked this nice button, congrats!');
};

<Button
    skin="secondary"
    onClick={onClick}>
    Click me dude
</Button>
```

```javascript
const onClick = () => {
    /* do click things */
    alert('Clicked this nice button, congrats!');
};

<Button
    skin="link"
    onClick={onClick}>
    Click me dude
</Button>
```

The buttons can also be used in combination with an icon.

```javascript
<Button skin="primary" icon="su-plus">
    Add something
</Button>
```

```javascript
<Button skin="secondary" icon="su-plus">
    Add something
</Button>
```

```javascript
<Button skin="link" icon="su-plus">
    Add something
</Button>
```

The prop `showDropdownIcon` displays a drop down icon on the right side.

```javascript
<Button skin="icon" icon="su-plus" showDropdownIcon={true} />
```

```javascript
<Button skin="primary" showDropdownIcon={true}>
    Add something
</Button>
```

```javascript
<Button skin="secondary" showDropdownIcon={true}>
    Add something
</Button>
```

```javascript
<Button skin="link" showDropdownIcon={true}>
    Add something
</Button>
```

It's also possible to have a icon and a drop down icon.

```javascript
<Button icon="su-plus" skin="primary" showDropdownIcon={true}>
    Add something
</Button>
```