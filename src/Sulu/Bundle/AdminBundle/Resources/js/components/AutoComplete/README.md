The AutoComplete is an input-field with auto-completion feature. The AutoComplete has no filter logic. That has to be 
done inside another component which afterwards will adjust the list of suggestions based on the entered input. 
To display the suggestions you pass the data as the `suggestions` props to the component. 

Here a basic example (Pssh, look for your favourite Harry Potter character):

```javascript
const AutoComplete = require('./AutoComplete').default;

initialState = {
    value: '',
    loading: false,
    suggestions: [],
}

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

    setState(() => ({
        loading: !!value,
        suggestions: [],
    }));
    
    if (value) {
        // Fake Request
        setTimeout(() => {
            setState(() => ({
                loading: false,
                suggestions: data.filter((suggestion) => suggestion.name.match(regexp))
            }));
        }, 500);
    }
};

const handleChange = (value) => {
    setState(() => ({
        value: value,
        suggestions: [],
    }));
};

<AutoComplete
    displayProperty="name"
    loading={state.loading}
    onChange={handleChange}
    onSearch={handleSearch}
    placeholder="Enter something fun..."
    searchProperties={['name']}
    suggestions={state.suggestions}
    value={state.value}
/>
```
