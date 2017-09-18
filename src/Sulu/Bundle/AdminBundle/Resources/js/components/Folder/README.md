The Folder component is used display a folder in which other items can be contained. The component itself is just an 
interactive element for presentation purposes which offers an onClick handler.

```
const itemStyles = {
    marginRight: 30,
    marginBottom: 30,
    display: 'inline-block',
};

const handleClick = (id) => {
    alert(`Folder with the id "${id}" was clicked`)
};

<div>
    <div style={itemStyles}>
        <Folder
            id="1"
            meta="3 Objects"
            title="This is a folder"
            onClick={handleClick}
        />
    </div>
    <div style={itemStyles}>
        <Folder
            id="2"
            meta="0 Objects"
            title="I am empty"
            onClick={handleClick}
        />
    </div>
    <div style={itemStyles}>
        <Folder
            id="3"
            meta="1 Object"
            title="I have 1 child inside me"
            onClick={handleClick}
        />
    </div>
    <div style={itemStyles}>
        <Folder
            id="4"
            meta="10 Objects"
            title="And I have 10 children. Wuhuu!"
            onClick={handleClick}
        />
    </div>
    <div style={itemStyles}>
        <Folder
            id="5"
            meta="2 Objects"
            title="Jeez, these folders..."
            onClick={handleClick}
        />
    </div>
</div>
```
