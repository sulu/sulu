The `MediaHistory` view is registered with the key `sulu_media.history`. It shows a list of different versions of a file
using the `ResourceStore` it receives from the [`ResourceTabs`](#resourcetabs) view. It also allows opening the old
versions in a new window.

| Option  | Description                                                             |
|---------|-------------------------------------------------------------------------|
| locales | Defines which locales are available in the locale chooser of this view.  |

```json static
{
    "name": "sulu_media.history",
    "view": "sulu_media.history",
    "options": {"locales": ["en", "de"]}
}
```
