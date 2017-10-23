The 'InfiniteScroller' component enables a pagination where the user will need to scroll to the bottom of the page to 
receive further content. The component finds its scrollable container automatically by traversing the DOM-Tree upwards
until it detects a container with the styling `overflow: scroll` or `overflow: auto` applied.

```javascript static
const handleLoad = (pageToLoad) => {
    // do something
};

<div id="scrollable">
    <InfiniteScroller
        total={10}
        current={1}
        onLoad={handleLoad}
    >
        <div className="content-item" />
        <div className="content-item" />
        <div className="content-item" />
        <div className="content-item" />
    </InfiniteScroller>
</div>
```
