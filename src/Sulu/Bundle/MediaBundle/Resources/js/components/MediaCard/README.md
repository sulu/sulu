
The MediaCard is one kind of child component of the Masonry layout. Due to the props of this component being an
intersection type with `MasonryItem` the `id` prop is required.

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
>
    <img src="http://lorempixel.com/300/200"/>
</MediaCard>
```
