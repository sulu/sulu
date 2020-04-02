The `Masonry` component just serves as a container. The children of the `Masonry` could be any kind of React component. 
The only requirement for the Masonry to work correctly is that every child needs a unique invariable `key`, which
disqualifies the `index` argument inside a `map` callback. The component recognizes appended, prepended and removed 
child elements and adjusts the masonry layout appropriately.
**Note:** Adding elements inbetween of existing children may cause incomprehensible and/or inconsistent behaviour. 

Here a basic example of the Masonry view:

```javascript
const [items, setItems] = React.useState([
    { id: 1, color: '#4a86e8', height: 300 },
    { id: 2, color: '#bb5ac4', height: 200 },
    { id: 3, color: '#5ac4b2', height: 150 },
]);

const createItems = () => {
    let id = Date.now();

    return [
        { id: id + 1, color: '#c4825a', height: 200 },
        { id: id + 2, color: '#96c45a', height: 250 },
        { id: id + 3, color: '#c45a72', height: 180 },
    ];
};

const prepend = () => {
    setItems([...createItems(), ...items]);
};

const append = () => {
    setItems([...items, ...createItems()]);
};

const remove = () => {
    setItems(items.filter((item, index) => index !== 3));
};

<div>
    <div style={{marginBottom: 30}}>
        <button onClick={() => prepend()}>Prepend</button>        
        <button onClick={() => append()}>Append</button>
        <button onClick={() => remove()}>Remove</button>
    </div>
    <Masonry>
        {
            items.map((item) => {
                return (
                    <div
                        key={item.id}
                        style={{
                            width: '260px',
                            height: item.height,
                            borderRadius: 6,
                            backgroundColor: item.color,
                        }}
                    />
                )
            })
        }
    </Masonry>
</div>
```
