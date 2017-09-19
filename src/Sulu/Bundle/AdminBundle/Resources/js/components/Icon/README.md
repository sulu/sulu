This is a simple component which renders icons. It uses the [Font Awesome Icon Toolkit](http://fontawesome.io/).

Pass a name to the component, and it will render the corresponding icon:

```
<Icon name="floppy-o" />
```

It can also take an additional `className`, which will be added to the class of the resulting `span` tag:

```
<Icon name="trash-o" className="special-icon" />
```

An icon can also have a `onClick` handler:

```
function handleClick() {
    alert('No action for you!');
}

<Icon name="bars" onClick={handleClick} />
```
