A card collection allows to handle multiple cards. Cards can be easily removed by clicking their remove icon and added
and edited using an overlay.

```javascript
initialState = {
    value: [
        {name: 'Harry Potter', title: 'Student'},
        {name: 'Albus Dumbledore', title: 'Headmaster'},
        {name: 'Severus Snape', title: 'Teacher'},
        {name: 'Ron Weasley', title: 'Student'},
        {name: 'Hermione Granger', title: 'Student'}
    ],
};

const renderCardContent = (cardData) => (
    <div>
        <strong>{cardData.name}</strong>
        <br />
        <em>{cardData.title}</em>
    </div>
);

<CardCollection renderCardContent={renderCardContent} value={state.value} />
```
