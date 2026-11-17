The `MediaFormats` view is registered with the key `sulu_media.formats`. It shows a list of avaible image formats of a
file using the `ResourceStore` it receives from the [`ResourceTabs`](#resourcetabs) view. It also allows opening the
each image format as well as copying the URL to this image format.

| Option  | Description                                                             |
|---------|-------------------------------------------------------------------------|
| locales | Defines which locales are available in the locale chooser of this view.  |

```json static
{
    "name": "sulu_media.formats",
    "type": "sulu_media.formats",
    "options": {"locales": ["en", "de"]}
}
```
