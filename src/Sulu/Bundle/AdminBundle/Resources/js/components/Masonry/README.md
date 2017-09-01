The Masonry component just serves as a container. It simplifies the management of the items inside by providing
handlers for selection and clicking on items. The children of the Masonry could be any kind of React component. 
The only requirement for the Masonry to work correctly is that every child needs a unique invariable `key`, which
disqualifies the `index` argument inside a `map` callback. It is recommended that the Prop types of the items
are intersection types which use the `MasonryItem` type defined inside the `types.js` file of the Masonry component.

Here a basic example of the Masonry view using the `MasonryMediaIte` component as a child.

```
const MasonryMediaItem = require('../MasonryMediaItem').default;

initialState = {
    selectedIds: [],
    items: [
        { id: 1, size: '260/350', title: 'This is a boring title', meta: 'bo and ring' },
        { id: 2, size: '260/260', title: 'Is this one better?', meta: 'No' },
        { id: 3, size: '260/300', title: 'But now!', meta: 'Hmm, not sure' },
        { id: 4, size: '260/260', title: 'You want to have a fight?', meta: 'Come at me!' },
        { id: 5, size: '260/380', title: 'LOL', meta: 'Yea, I thought so' },
        { id: 6, size: '260/200', title: 'Now back to the Masonry', meta: ':)' },
        { id: 7, size: '260/400', title: 'This is an image', meta: 'You are so smart' },
        { id: 8, size: '260/180', title: 'This image has meta info', meta: 'No' },
        { id: 9, size: '260/250', title: 'Dude, cmon', meta: 'NO' },
        { id: 10, size: '260/200', title: 'Pls, you are embarrassing me', meta: 'Ugh, ok' },
        { id: 11, size: '260/150', title: 'An image', meta: 'image/png, 3,2 MB' },
    ]
};

const createItems = () => {
    let id = Date.now();

    return [
        { id: id + 1, size: '260/190', title: 'Hey guys, wazup?' },
        { id: id + 2, size: '260/210', title: 'I am a new Item :)' },
        { id: id + 3, size: '260/230', title: 'Mee toooo :*' },
    ];
};

const isSelected = (id) => {
    return state.selectedIds.includes(id);
};

const handleItemSelectionChange = (id, checked) => {
    if (checked) {
        state.selectedIds.push(id);

        setState({
            selectedIds: state.selectedIds,
        });
    } else {
        setState({
            selectedIds: state.selectedIds.filter((selectedId) => selectedId !== id)
        });
    }
};

const handleItemClick = (id) => {
    alert(`You clicked me and my id is "${id}"`);
};

const prepend = () => {
    state.items.unshift(...(createItems()));

    setState({
        items: state.items,
    });
};

const append = () => {
    state.items.push(...(createItems()));

    setState({
        items: state.items,
    });
};

const remove = () => {
    setState({
        items: state.items.filter((item, index) => index !== 3),
    });
};

<div>
    <div style={{marginBottom: 30}}>
        <button onClick={() => prepend()}>Prepend</button>        
        <button onClick={() => append()}>Append</button>
        <button onClick={() => remove()}>Remove</button>
    </div>
    <Masonry
        onItemSelectionChange={handleItemSelectionChange}
        onItemClick={handleItemClick}>
        {
            state.items.map((item) => {
                return (
                    <MasonryMediaItem
                        key={item.id}
                        id={item.id}
                        icon="heart"
                        selected={isSelected(item.id)}
                        metaInfo={item.meta}
                        mediaTitle={item.title}>
                        <img src={`http://lorempixel.com/${item.size}`} />
                    </MasonryMediaItem>
                )
            })
        }
    </Masonry>
</div>
```
