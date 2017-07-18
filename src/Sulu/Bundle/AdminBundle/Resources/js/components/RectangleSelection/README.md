This is a general purpose component to select an area on all kind of content.
The properties allow to specify a minimum width as well as a minimum height,
which the selection box cannot undercut.
If both a minimum height and a minimum width is given, the ratio between these two is enforced.
A double click on the selection box centers it and maximizes its size.

```
<RectangleSelection
    initialSelection={{width: 300, height: 100, left: 50, top: 50}}>
    <div>
        <p style={{padding: '20px'}}>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>
    </div>
</RectangleSelection>
```

As content to be selected is often loaded asynchronously, the component provides a mechanism to
delay the initialization of the selection box till the desired content is fully loaded.
To achieve this behaviour, a function returning a promise can be passed via the properties.
The following example illustrates this functionality.

```
let image;
let imageLoaded = () => {
    return new Promise((resolve, reject) => {
        image.onload = resolve;
        image.onerror = reject;
    });
};
initialState = {selection: {}};

<div>
    <RectangleSelection
        minWidth={150}
        minHeight={50}
        childrenFullyLoaded={imageLoaded}
        onChange={s => setState({selection: s})}
        >
        <img ref={el => image = el} src="https://unsplash.it/800/500" />
    </RectangleSelection>
    
    <p>
        Width: {state.selection.width}, 
        Height: {state.selection.height}, 
        Top: {state.selection.top}, 
        Left: {state.selection.left}
    </p>
</div>
```
