This component is the counterpart of the [`SingleAutoComplete`](#singleautocomplete) component, but it supports
assigning multiple items to it.

```javascript
const [value, setValue] = React.useState([]);
const [loading, setLoading] = React.useState(false);
const [suggestions, setSuggestions] = React.useState([]);

const data = [
    {id: 1, name: 'Donald Duck'},
    {id: 2, name: 'Mickey Mouse'},
    {id: 3, name: 'Dagobert Duck'},
    {id: 4, name: 'Tick Duck'},
    {id: 5, name: 'Trick Duck'},
    {id: 6, name: 'Track Duck'},
    {id: 7, name: 'Minney Mouse'},
    {id: 8, name: 'Goofey'},
    {id: 9, name: 'Superman'},
    {id: 10, name: 'Batman'},
    {id: 11, name: 'Harry Potter'},
    {id: 12, name: 'Lilly Potter'},
    {id: 13, name: 'James Potter'},
    {id: 14, name: 'Albus Dumbledore'},
    {id: 15, name: 'Severus Snape'},
    {id: 16, name: 'Ron Weasly'},
    {id: 17, name: 'Hermoine Granger'},
    {id: 18, name: 'Tom Riddle'},
    {id: 19, name: 'Bathilda Bagshot'},
    {id: 20, name: 'Susan Bones'},
    {id: 21, name: 'Marvolo Gaunt'},
    {id: 22, name: 'Godric Gryffindor'},
];

const handleSearch = (value) => {
    const regexp = new RegExp(value, 'gi');

    setLoading(!!value);
    setSuggestions([]);

    if (value) {
        // Fake Request
        setTimeout(() => {
            setLoading(false);
            setSuggestions(data.filter((suggestion) => suggestion.name.match(regexp)));
        }, 500);
    }
};

const handleChange = (value) => {
    setValue(value);
    setSuggestions([]);
};

<MultiAutoComplete
    displayProperty="name"
    loading={loading}
    onChange={handleChange}
    onSearch={handleSearch}
    placeholder="Enter something fun..."
    searchProperties={['name']}
    suggestions={suggestions}
    value={value}
/>
```

If the `allowAdd` prop is set to true, then the user can also add new items on its own.

```javascript
const [value, setValue] = React.useState([]);
const [loading, setLoading] = React.useState(false);
const [suggestions, setSuggestions] = React.useState([]);

const data = [
    {name: 'Harry Potter'},
    {name: 'Lilly Potter'},
    {name: 'James Potter'},
    {name: 'Albus Dumbledore'},
    {name: 'Severus Snape'},
    {name: 'Ron Weasly'},
    {name: 'Hermoine Granger'},
    {name: 'Tom Riddle'},
    {name: 'Bathilda Bagshot'},
    {name: 'Susan Bones'},
    {name: 'Marvolo Gaunt'},
    {name: 'Godric Gryffindor'},
];

const handleSearch = (value) => {
    const regexp = new RegExp(value, 'gi');

    setLoading(!!value);
    setSuggestions([]);

    if (value) {
        // Fake Request
        setTimeout(() => {
            setLoading(false);
            setSuggestions(data.filter((suggestion) => suggestion.name.match(regexp)));
        }, 500);
    }
};

const handleChange = (value) => {
    setValue(value);
    setSuggestions([]);
};

<MultiAutoComplete
    allowAdd={true}
    displayProperty="name"
    idProperty="name"
    loading={loading}
    onChange={handleChange}
    onSearch={handleSearch}
    placeholder="Enter something fun..."
    searchProperties={['name']}
    suggestions={suggestions}
    value={value}
/>
```
