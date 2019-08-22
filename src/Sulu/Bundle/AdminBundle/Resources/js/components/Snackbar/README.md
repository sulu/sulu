The `Snackbar` componetn is used to display information that is moving in from the top of the screen using an animation.

```javascript
<Snackbar type="error" />
```

Besides errors it can also show warnings:

```javascript
<Snackbar type="warning" />
```

There are two different callbacks that can be added. One is the `onCloseClick` callback, which makes a close button
appear calling this callback when it is clicked.

```javascript
const closeClickHandler = () => alert('The snackbar should be closed now');

<Snackbar onCloseClick={closeClickHandler} type="error" />
```

The second one is the `onClick` callback, which is called whenever the snackbar is clicked.

```javascript
const clickHandler = () => alert('The snackbar was clicked');

<Snackbar onClick={clickHandler} type="error" />
```

The toolbar is also animated when it (dis)appears.

```javascript
initialState = {
    visible: true,
};

const clickHandler = () => setState({visible: !state.visible});

<div style={{overflow: 'hidden'}}>
    <Snackbar type="error" visible={state.visible} />
    <button onClick={clickHandler}>Toggle Snackbar</button>
</div>
```
