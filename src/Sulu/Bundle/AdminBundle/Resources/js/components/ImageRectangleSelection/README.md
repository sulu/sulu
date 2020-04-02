This component builds upon the `RectangleSelection` and defines more specific functionality,
useful when dealing with large images. Imagine loading a large 1920x1080 image.
Most often you want the user to select a section from a scaled down version of this large image.
However, the data returned by the selection component should be with respect to the large image
(e.g. for cropping the image in the backend). This is exactly the functionality of this component.
This component loads an image, makes sure it takes up the maximum width and height provided by its container
without distorting the image and renders a selection component on top of it.

```javascript
const initialSelection = {width: 1000, height: 800, top: 200, left: 300};

const [selection, setSelection] = React.useState(initialSelection);

<div style={{width: 500, height: 500, background: '#e8e8e8'}}>
    <ImageRectangleSelection
        image="https://picsum.photos/1920/1080"
        onChange={setSelection}
        value={selection}
    />
</div>
```

Like with the `RectangleSelection`, if both the `minWidth` and `minHeight` properties are set,
the ratio between these two is enforced on the selection.

```javascript
const [selection, setSelection] = React.useState(undefined);

<div>
    <div style={{width: 800, height: 300, background: '#e8e8e8'}}>
        <ImageRectangleSelection
            image="https://picsum.photos/1920/1080"
            initialSelection={{width: 1500, height: 800, top: 200, left: 300}}
            minWidth={100}
            minHeight={60}
            onChange={setSelection}
            value={selection}
        />
    </div>
    
    {selection &&
        <p>
            Width: {selection.width},
            Height: {selection.height},
            Top: {selection.top},
            Left: {selection.left}
        </p>
    }
</div>
```
