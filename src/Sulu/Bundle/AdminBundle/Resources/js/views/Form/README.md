The form is registered with the key `sulu_admin.form`. It shows a Form with a toolbar. The location to which the back
button redirects can be configured using the route's options. In addition to that it also takes the locales, which
should be shown in the toolbar and an instance of the `ResourceStore`, which allows to load and save data.

| Option        | Description                                                                                         |
|---------------|-----------------------------------------------------------------------------------------------------|
| backRoute     | The route to which the user will be navigate when the back button is clicked.                       |
| editRoute     | The optional route to which the user will be redirected after the form is saved.                    |
| locales       | Defines which locales are available in the locale chooser of this form.                             |
| resourceStore | The store which allows to save and load data from the given type of resources                       |
