The `Requester` is a thin abstraction layer around the `fetch` javascript function. It adds the `same-origin`
credential option, in order to send all the cookies in each request, since this is not the default, and we are not
authorized against our REST API otherwise.

The methods return a promise, in which the `json` method has already been called, and therefore this method should not
be called afterwards again.

```javascript static
Requester.get('/admin/api/snippets')
    .then((response) => {
        console.log(response._embedded.snippets);
    });
```
