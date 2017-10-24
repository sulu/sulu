The 'InfiniteScroller' component enables a pagination where the user will need to scroll to the bottom of the page to 
receive further content. The component finds its scrollable container automatically by traversing the DOM-Tree upwards
until it detects a container with the styling `overflow: scroll` or `overflow: auto` applied.

```
const getBackgroundColor = () => {
    const colors = [
        '#7CB9E8',
        '#E52B50',
        '#FFBF00',
        '#9966CC',
        '#007FFF',
        '#FF91AF',
    ];

    return colors[Math.floor(Math.random() * colors.length)]
};

initialState = {
    page: 1,
    items: [1, 2, 3, 4, 5, 6, 7, 8, 9].map(() => getBackgroundColor(), []),
    loading: false,
};

const handleLoad = (pageToLoad) => {
    setState({
        page: pageToLoad,
        loading: true,
    });

    setTimeout(() => {
        const newItems = [];
        const length = state.items.length;

        for (let i = length; i < length + 5; i++) {
            newItems.push(getBackgroundColor());
        }

        state.items.push(...newItems);

        setState({
            items: state.items,
            loading: false,
        });
    }, 1000);
};

const containerStyle = {
    height: 600,
    overflow: 'scroll',
};

const itemStyle = {
    height: 150,
    marginBottom: 5,
};

const loadingContainerStyle = {
    paddingTop: 20,
    height: 100,
};

<div style={containerStyle}>
    <InfiniteScroller
        total={10}
        current={state.page}
        onLoad={handleLoad}
    >
        {state.items.map((value, index) => (
            <div key={index} style={{...itemStyle, backgroundColor: value}} />
        ))}
    </InfiniteScroller>
    <div style={loadingContainerStyle}>
        {state.loading &&
            <Loader size={20} />
        }
    </div>
</div>
```
