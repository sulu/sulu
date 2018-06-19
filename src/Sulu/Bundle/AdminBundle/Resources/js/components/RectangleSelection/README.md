This is a general purpose component to select an area on all kind of content.
The properties allow to specify a minimum width as well as a minimum height,
which the selection box cannot undercut.
If both a minimum height and a minimum width is given, the ratio between these two is enforced.
A double click on the selection box centers it and maximizes its size.

```javascript
<RectangleSelection
    initialSelection={{width: 300, height: 100, left: 50, top: 50}}>
    <div>
        <p style={{padding: '20px'}}>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>
    </div>
</RectangleSelection>
```

Content placed inside the component is not allowed to change it's size after rendering.
Otherwise, the behaviour will be undefined.
For example when rendering images, they need to be preloaded before rendering the selection.

```javascript
// preload image
let image = new Image();
image.src = 'https://unsplash.it/800/500';
initialState = {imageLoaded: image.complete, selection: {}};
state.imageLoaded
    ? <div>
        <RectangleSelection
            minWidth={115}
            minHeight={100}
            onChange={s => setState({selection: s})}>
            <img src="https://unsplash.it/800/500" />
        </RectangleSelection>
        
        <p>
            Width: {state.selection.width}, 
            Height: {state.selection.height}, 
            Top: {state.selection.top}, 
            Left: {state.selection.left}
        </p>
    </div>
    : null
```
