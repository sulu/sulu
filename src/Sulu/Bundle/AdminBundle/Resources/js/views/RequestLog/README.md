The `RequestLog` view lists the requests an installation sent to an external service, with a detail overlay
showing the resolved chain (system prompt, user prompt, response) plus timing and credits.
It embeds the stock `containers/List` against the `request_log` resource/list key instead of using `views/List`,
because the detail overlay is a hand-built panel rather than a stock form.

The registering extension provides the REST endpoints, the resource and the translations. It passes two view
options: `detailRoute`, the name of the route that returns one request, and `translationPrefix`, the prefix of the
translation keys the view reads (for example `my_extension.request_log.`).
