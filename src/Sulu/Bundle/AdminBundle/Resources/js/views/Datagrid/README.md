The datagrid is registered with the key `sulu_admin.datagrid`. It shows a Datagrid container with a toolbar.
The toolbar shows an add and a delete button, whereby the latter one only appears enabled
if something in the Datagridhas been selected.

The view uses the options passed via the current route to adapt its behavior. The following table explains the meanings
of the available options:

| Option      | Description                                                                                           |
|-------------|-------------------------------------------------------------------------------------------------------|
| resourceKey | A key offered by the server, which describes a certain type of entity, for which data will be loaded. |
| editRoute   | The route, which can hold a `id` parameter to which a click on the pencil button will redirect.     |
