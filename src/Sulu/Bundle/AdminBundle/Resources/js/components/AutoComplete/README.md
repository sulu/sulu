The AutoComplete is an input-field with auto-completion feature. The AutoComplete has no filter logic. That has to be 
done inside another component which afterwards will adjust the list of suggestions based on the entered input. 
To display the suggestions you can use the `Suggestion` component. The displayed value of a `Suggestion` can be a 
simple text or if you need further customization you can wrap HTML markup into a function and place that as the child 
of the `Suggestion`. In that case you have to use the `highlight` function to highlight the matched suggestion text.

Here a basic example (Pssh, look for your favourite Harry Potter character):

```javascript
const AutoComplete = require('./AutoComplete').default;
const Suggestion = AutoComplete.Suggestion;

initialState = {
    value: '',
    loading: false,
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
                suggestions: data.filter((suggestion) => suggestion.match(regexp))
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
    value={state.value}
    onChange={handleChange}
    onSearch={handleSearch}
    loading={state.loading}
    placeholder="Enter something fun..."
>
    {
        state.suggestions.map((suggestion, index) => {
            return (
                <Suggestion
                    key={index}
                    icon="fa-ticket"
                    value={suggestion}
                >
                    {(highlight) => (
                        <div>{highlight(suggestion)}</div>
                    )}
                </Suggestion>
            );
        })
    }
</AutoComplete>
```
