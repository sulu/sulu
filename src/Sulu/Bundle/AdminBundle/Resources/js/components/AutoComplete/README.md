The AutoComplete is an input-field with auto-completation feature. The AutoComplete has no filter logic. That has to be 
done inside a HoC which afterwards will adjust the list of suggestions based on the entered input. To display the 
suggestions you can use the Suggestion component.

Here a basic example (Pssh, look for your favourite Harry Potter character):

```
const Suggestion = require('./Suggestion').default;

initialState = {
    value: '',
    suggestions: [],
}

const data = [
    'Donald Duck',
    'Mickey Mouse',
    'Dagobert Duck',
    'Tick Duck',
    'Trick Duck',
    'Track Duck',
    'Minney Mouse',
    'Goofey',
    'Superman',
    'Batman',
    'Harry Potter',
    'Lilly Potter',
    'James Potter',
    'Albus Dumbledore',
    'Severus Snape',
    'Ron Weasly',
    'Hermoine Granger',
    'Tom Riddle',
    'Bathilda Bagshot',
    'Susan Bones',
    'Marvolo Gaunt',
    'Godric Gryffindor',
];

const handleChange = (value) => {
    const regexp = new RegExp(value, 'gi');

    setState(() => ({
        value: value,
        suggestions: data.filter((suggestion) => suggestion.match(regexp))
    }));
};

<AutoComplete
    placeholder="Enter something fun..."
    value={state.value}
    threshold={1}
    inputIcon="search"
    noSuggestionsMessage="Nothing found..."
    onChange={handleChange}>
    {
        state.suggestions.map((suggestion, index) => {
            return (
                <Suggestion
                    key={index}
                    icon="ticket"
                    value={suggestion} />
            );
        })
    }
</AutoComplete>
```
