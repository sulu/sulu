The `RequestLog` view lists every request the installation sent to the sulu.ai platform, with a detail overlay
showing the resolved chain (system/expert instructions, user prompt, assistant response) plus timing and credits.
It embeds the stock `containers/List` against the `request_log` resource/list key instead of using `views/List`,
because the detail overlay is a hand-built resolved-chain panel rather than a stock form. Route, REST endpoints
and translations are provided by the AI platform bundle — the view is only registered when that bundle configures
the matching view type.
