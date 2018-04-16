The `ButtonGroup` component displays a group of buttons.

Example with two buttons:

```javascript
const onClick = () => {
    /* do click things */
    alert('Clicked this nice button, congrats!');
};

<ButtonGroup>
    <Button onClick={onClick}>
        <Icon name="su-th-large" />
    </Button>
    <Button onClick={onClick}>
        <Icon name="su-align-justify" />
    </Button>
</ButtonGroup>
```

Example with three buttons:

```javascript
const onClick = () => {
    /* do click things */
    alert('Clicked this nice button, congrats!');
};

<ButtonGroup>
    <Button active={true} onClick={onClick}>
        <Icon name="xxx" />
    </Button>
    <Button onClick={onClick}>
        <Icon name="su-align-justify" />
    </Button>
    <Button onClick={onClick}>
        <Icon name="su-th-large" />
    </Button>
</ButtonGroup>
```

It's also possible to have just one button inside:

```javascript
const onClick = () => {
    /* do click things */
    alert('Clicked this nice button, congrats!');
};

<ButtonGroup>
    <Button onClick={onClick}>
        <Icon name="su-th-large" />
    </Button>
</ButtonGroup>
```