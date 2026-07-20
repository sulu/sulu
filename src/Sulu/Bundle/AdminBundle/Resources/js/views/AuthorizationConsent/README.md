The authorization-consent is registered with the key `sulu_admin.authorization_consent`. It shows a fullscreen consent
screen that asks the user to approve or deny an OAuth authorization request. The details of the request, like the name
of the requesting application and the requested scopes, are loaded from the server and the user's decision is sent back
to it.

The view resolves the `requestId` from the current route's attributes and passes it to the routes configured via the
options below. The following table explains the meanings of the available options:

| Option        | Description                                                                                       |
|---------------|---------------------------------------------------------------------------------------------------|
| detailsRoute  | Name of the Symfony route from which the consent details (application, redirect URI and scopes)   |
|               | are loaded.                                                                                       |
| decisionRoute | Name of the Symfony route to which the approve or deny decision of the user is sent.              |
