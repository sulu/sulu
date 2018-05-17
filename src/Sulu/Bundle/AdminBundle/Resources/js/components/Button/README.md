The `Button` component displays a button.

```
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

```
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

```
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
