The FolderList component is used display a list of folders. The FolderList accepts Folder componenta as children.
The Folder component is just an interactive element for presentation purposes. Every Folder component has its
own onClick-handler. The FolderList component offers a more convenient way to work with multiple folders by offering 
a single onClick-handler.

Folder Example:

```
const Folder = require('./Folder').default;

const handleClick = (id) => {
    alert(`You clicked on a Folder, wohooo, do you want a cookie now?`)
};

<Folder
    id="1"
    meta="3 Objects"
    title="This is a folder"
    onClick={handleClick}
/>
```

FolderList Example:

```
const handleClick = (id) => {
    alert(`Folder with the id "${id}" was clicked`)
};

<FolderList onFolderClick={handleClick}>
    <FolderList.Folder
        id="1"
        meta="3 Objects"
        title="This is a folder"
    />
    <FolderList.Folder
        id="2"
        meta="0 Objects"
        title="I am empty"
    />
    <FolderList.Folder
        id="3"
        meta="1 Object"
        title="I have 1 child inside me"
    />
    <FolderList.Folder
        id="4"
        meta="10 Objects"
        title="And I have 10 children. Wuhuu!"
    />
    <FolderList.Folder
        id="5"
        meta="2 Objects"
        title="Jeez, these folders..."
    />
    <FolderList.Folder
        id="6"
        meta="4 Objects"
        title="el oh el"
    />
</FolderList>
```
