The `ResourceRequester` is built for talking to APIs as they are built in Sulu. It already knows how this API is built
and sends the correct requests resp. handles the responses correctly.

There are a few methods that can be used to talk to these APIs. They all share the same methods, and the first
parameter is the `resourceKey`, which defines the entity.

```javascript static
ResourceRequester.getList('contacts', {page: 2})
    .then((response) => {
        // outputs the second page of contacts
        console.log(response._embedded.contacts);
    });

// get only the snippet with the ID 2 and send the locale as query parameter
ResourceRequester.get('snippets', {id: 2, locale: 'en'});

// update the snippet with the ID 3 and send the locale as query parameter
ResourceRequester.put('snippets', {title: 'Title'}, {id: 3, locale: 'en'});

// delete the snippet with the ID 6
ResourceRequester.delete('snippets', {id: 6});
```
