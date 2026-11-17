This view allows to assign snippets to snippet areas. It is registered in the `ViewRegistry` with the key
`sulu_snippet.snippet_areas`.

It lists the snippet areas of the webspace given by the `webspace` router attribute (required, has to be a string) in
a table. A snippet can be assigned to an area using a list overlay, and the assignment can be removed again. The toolbar
contains the `CacheClearToolbarAction` of the webspace.

| Option            | Description                                                                          |
|-------------------|--------------------------------------------------------------------------------------|
| snippetEditView   | Name of the view the router navigates to when a snippet in the table is clicked.     |

```json static
{
    "name": "sulu_snippet.snippet_areas",
    "type": "sulu_snippet.snippet_areas",
    "path": "/snippet-areas",
    "options": {"snippetEditView": "sulu_snippet.edit_form"}
}
```
