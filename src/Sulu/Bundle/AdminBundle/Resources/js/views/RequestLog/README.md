The `RequestLog` view lists the requests an installation sent to an external service, with a detail overlay
showing the resolved chain (system prompt, user prompt, response) plus timing and credits.
It embeds the stock `containers/List` against the `request_log` resource/list key instead of using `views/List`,
because the detail overlay is a hand-built panel rather than a stock form.

Registered under `sulu_ai_platform.request_log` — the view belongs to the AI platform bundle, which provides the
REST endpoints, the resource and the translations. It passes one view option, `translationPrefix`, the prefix of
the translation keys the view reads (for example `my_extension.request_log.`). The detail overlay loads through
`ResourceRequester.get('request_log', {id})`, the same `request_log` detail route the list's row delete already
requires.
