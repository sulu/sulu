The `Subscription` view shows the booked sulu.ai plan with its status and the AI credit usage of the current month.
It loads its data from the route configured under the `sulu_ai_platform.subscription` admin config, which also
provides the contact email shown in the contact box below the cards. Route, endpoint and translations are provided
by the AI platform bundle — the view is only registered when that bundle configures the matching view type.
