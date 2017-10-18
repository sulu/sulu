The `Button` component let's display a button.

```
const onClick = () => {
    /* do click things */
    alert('Clicked this nice button, congrats!');
};

<div>
    <Button
        type="confirm"
        onClick={onClick}>
        Click me dude
    </Button>
</div>
```

```
const onClick = () => {
    /* do click things */
    alert('Clicked this nice button, congrats!');
};

<div>
    <Button
        type="cancel"
        onClick={onClick}>
        Click me dude
    </Button>
</div>
```
