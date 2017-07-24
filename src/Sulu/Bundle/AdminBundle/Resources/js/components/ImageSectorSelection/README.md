This component builds upon the RectangleSelection and defines more specific functionality
useful when dealing with large images.
Imagine loading a large 1920x1080 image and scaling it down to 640x360 via CSS.
When selecting a sector of the image, it is (depending on the use case) desired to
get the coordinates of the selection (width, height, top and left) with respect to
the original dimensions of the image and not the scaled down ones.
This is exactly what this component does.

```
<div style={{width: 640, height: 360}}>
    <ImageSectorSelection
        initialSelection={{width: 1000, height: 800, top: 200, left: 300}}
        imageSrc="https://unsplash.it/1920/1080" />
</div>
```

Like with the RectangleSelection, if both the minWidth and minHeight properties are set,
the ratio between this to is enforced on the selection.

```
initialState = {selection: {}};
<div>
    <div style={{width: 640, height: 360}}>
        <ImageSectorSelection
            initialSelection={{width: 1500, height: 800, top: 200, left: 300}}
            minWidth={100}
            minHeight={60}
            imageSrc="https://unsplash.it/1920/1080"
            onChange={s => setState({selection: s})}
        />
    </div>
    
    <p>
        Width: {state.selection.width}, 
        Height: {state.selection.height}, 
        Top: {state.selection.top}, 
        Left: {state.selection.left}
    </p>
</div>
```
