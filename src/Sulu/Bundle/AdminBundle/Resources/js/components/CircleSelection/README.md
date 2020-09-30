This is a general purpose component to select a circle area on all kind of content. The properties allow to specify a
minimum radius as well as a maximum radius, which the selection box cannot undercut. A double click on the selection box
centers it and maximizes its size, if it's resizable.

```javascript
const initialSelection = {radius: 100, left: 50, top: 50};

const [selection, setSelection] = React.useState(initialSelection);

<div>
    <CircleSelection
        onChange={setSelection}
        value={selection}
    >
        <div>
            <p style={{padding: '20px'}}>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>
        </div>
    </CircleSelection>

    {selection &&
        <p>
            Radius: {selection.radius}<br />
            Left: {selection.left}<br />
            Top: {selection.top}
        </p>
    }

    <button onClick={() => setSelection(initialSelection)}>Reset to initial value</button>
</div>
```

Content placed inside the component is not allowed to change it's size after rendering.  Otherwise, the behaviour will
be undefined. For example when rendering images, they need to be preloaded before rendering the selection.

```javascript
// preload image
let image = new Image();
image.src = 'https://unsplash.it/800/500';

const [imageLoaded, setImageLoaded] = React.useState(image.complete);
const [selection, setSelection] = React.useState({radius: 200, top: 100, left: 100});

image.onload = () => setImageLoaded(true);

imageLoaded
    ? <div>
        <CircleSelection
            minRadius={100}
            maxRadius={200}
            onChange={setSelection}
            value={selection}
            label="1"
        >
            <img src="https://unsplash.it/800/500"/>
        </CircleSelection>

        <p>
            Radius: {selection.radius}, 
            Top: {selection.top}, 
            Left: {selection.left}
        </p>
    </div>
    : null
```

If the values should be in percent, set `usePercentageValues={true}`. Be aware, that the `radius` is relative to the `containerWidth` then.

```javascript
// preload image
let image = new Image();
image.src = 'https://unsplash.it/800/500';

const [imageLoaded, setImageLoaded] = React.useState(image.complete);
const [selection, setSelection] = React.useState({radius: 0.1, top: 0.5, left: 0.5});

image.onload = () => setImageLoaded(true);

imageLoaded
    ? <div>
        <CircleSelection
            label="1"
            minRadius={0.06}
            onChange={setSelection}
            usePercentageValues={true}
            value={selection}
        >
            <img src="https://unsplash.it/800/500"/>
        </CircleSelection>

        <p>
            Radius: {selection.radius},
            Top: {selection.top}, 
            Left: {selection.left}
        </p>
    </div>
    : null
```

There is also the possibility to render a PointSelection by setting `resizable={false}` and omitting the radius.

```javascript
// preload image
let image = new Image();
image.src = 'https://unsplash.it/800/500';

const [imageLoaded, setImageLoaded] = React.useState(image.complete);
const [selection, setSelection] = React.useState({top: 100, left: 100});

image.onload = () => setImageLoaded(true);

imageLoaded
    ? <div>
        <CircleSelection
            filled={true}
            label="55"
            resizable={false}
            onChange={setSelection}
            value={selection}
        >
            <img src="https://unsplash.it/800/500"/>
        </CircleSelection>

        <p>
            Top: {selection.top}, 
            Left: {selection.left}
        </p>
    </div>
    : null
```

To disable a CircleSelection, set `disabled={true}`.

```javascript
// preload image
let image = new Image();
image.src = 'https://unsplash.it/800/500';

const [imageLoaded, setImageLoaded] = React.useState(image.complete);
const [selection, setSelection] = React.useState({radius: 100, top: 200, left: 200});

image.onload = () => setImageLoaded(true);

imageLoaded
    ? <div>
        <CircleSelection
            disabled={true}
            onChange={setSelection}
            value={selection}
        >
            <img src="https://unsplash.it/800/500"/>
        </CircleSelection>

        <p>
            Radius: {selection.radius}, 
            Top: {selection.top}, 
            Left: {selection.left}
        </p>
    </div>
    : null
```

There is also the possibility, to render multiple CircleSelections for one single container. But you should use the
`withContainerSize` hoc to automatically pass `containerWidth` and `containerHeight` to the `CircleSelectionRenderer`
components. For the sake of simplicity it's hardcoded in this example.

```javascript
// preload image
let image = new Image();
image.src = 'https://unsplash.it/800/500';

const [imageLoaded, setImageLoaded] = React.useState(image.complete);
const [firstSelection, setFirstSelection] = React.useState({radius: 200, top: 100, left: 100});
const [secondSelection, setSecondSelection] = React.useState({radius: 100, top: 150, left: 500});
const [active, setActive] = React.useState(1);

image.onload = () => setImageLoaded(true);

imageLoaded
    ? <div>
        <div style={{ width: '800px', height: '500px', position: 'relative', overflow: 'hidden', display: 'inline-flex' }}>
            <img src="https://unsplash.it/800/500" style={{ userSelect: 'none', pointerEvents: 'none' }} alt="Unsplash image" />
            <CircleSelection.Renderer
                disabled={active !== 1}
                onChange={setFirstSelection}
                value={firstSelection}
                label="1"
                containerWidth={800}
                containerHeight={500}
            />
            <CircleSelection.Renderer
                disabled={active !== 2}
                onChange={setSecondSelection}
                value={secondSelection}
                label="2"
                containerWidth={800}
                containerHeight={500}
            />
        </div>
        <div>
            <p>Active: {active}</p>
            <button onClick={() => {setActive(1)}}>#1</button>
            <button onClick={() => {setActive(2)}}>#2</button>
        </div>
    </div>
    : null
```
