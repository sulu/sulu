The AutoComplete is an input-field with auto-completion feature. The AutoComplete has no filter logic. That has to be 
done inside another component which afterwards will adjust the list of suggestions based on the entered input. 
To display the suggestions you can use the Suggestion component. The displayed value of a `Suggestion` can be a simple 
text or if you need further customization you can wrap HTML markup into a function and place that as the child of the 
`Suggestion`. In that case you have to use the `highlight` function to highlight the matched suggestion text.

Here a basic example (Pssh, look for your favourite Harry Potter character):

```
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

const handleChange = (value) => {
    setState(() => ({
        value: value,
        loading: !!value,
    }));
};

const handleUpdate = (value) => {
    const regexp = new RegExp(value, 'gi');
    
    setState(() => ({
        loading: false,
        suggestions: data.filter((suggestion) => suggestion.match(regexp))
    }));
};

const handleSelection = (value) => {
    setState(() => ({
        value: value,
        loading: false,
    }));
};

<AutoComplete
    value={state.value}
    onChange={handleChange}
    onDebouncedChange={handleUpdate}
    onSuggestionSelection={handleSelection}
    loading={state.loading}
    placeholder="Enter something fun..."
    noSuggestionsMessage="Nothing found..."
>
    {
        state.suggestions.map((suggestion, index) => {
            return (
                <Suggestion
                    key={index}
                    icon="ticket"
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
