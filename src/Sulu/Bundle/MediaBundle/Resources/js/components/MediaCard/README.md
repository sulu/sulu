
The `MediaCard` is useful for displaying a list of selectable images.

```
initialState = {
    selected: false,
};

const handleSelection = () => {
    setState({
        selected: !state.selected,
    });
};

const handleClick = (id) => {
    alert(`You clicked me and my id is "${id}"`);
};

<MediaCard
    id="What is luv?"
    icon="pencil"
    onSelectionChange={handleSelection}
    onClick={handleClick}
    selected={state.selected}
    meta="image/png, 3,2 MB"
    title="Lorempixel"
    image={'http://lorempixel.com/300/200'}
/>
```
