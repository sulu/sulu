```
const MasonryItem = require('../MasonryMediaItem').default;

function createChildren(rnd) {
    return [
        {
            id: rnd + 1,
            text: `Erfan ${rnd}`
        },
        {
            id: rnd + 2,
            text: `Alex ${rnd}`
        },
        {
            id: rnd + 3,
            text: `Daniel ${rnd}`
        }
    ];
}

initialState = {
    show: true,
    showPrepend: false,
    prepended: [],
    items: createChildren(1, 100),
};

function getRandom(min, max) {
  return Math.random() * (max - min) + min;
}

prependItems = () => {
    state.items.unshift(...(createChildren(getRandom(1, 100))));
    setState({
        items: state.items,
    });
};

prependOne = () => {
    state.items.unshift({
        id: getRandom(1, 100),
        text: `Alex One`
    });

    setState({
        items: state.items,
    });
};

appendItems = () => {
    setState({
        items: state.items.concat(createChildren(getRandom(1, 100))),
    });
};

removeItems = () => {
    state.items.splice(3, 3);

    setState({
        items: state.items,
    });
};

editItem = () => {
    state.items[3].text = 'Lorem dipsum bkds sds dfsds das d asdasdasd asd sad, Lorem dipsum bkds sds dfsds das d asdasdasd asd sad';

    setState({
        items: state.items,
    });
};

<div>
    <button onClick={() => setState({ show: !state.show })}>Toggle visibility</button>
    <button onClick={() => prependItems()}>Prepend items</button>
    <button onClick={() => appendItems()}>Append items</button>    
    <button onClick={() => prependOne()}>Prepend</button>
    <button onClick={() => editItem()}>Edit Item</button> 
    <button onClick={() => removeItems()}>Remove Items</button>     

    {state.show &&
        <Masonry>
            {
                state.items.map((content) => {
                    return (
                        <MasonryItem key={content.id}>
                            <div>
                                {content.text}
                            </div>
                        </MasonryItem>
                    );
                })
            }
        </Masonry>
    }
</div>
```
