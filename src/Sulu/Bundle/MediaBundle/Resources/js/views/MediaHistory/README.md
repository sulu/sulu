The `MediaHistory` view is registered with the key `sulu_media.history`. It shows a list of different versions of a file
using the `ResourceStore` it receives from the [`ResourceTabs`](#resourcetabs) view. It also allows opening the old
versions in a new window.

The view is deprecated since 3.1. The media history tab is a [`List`](#list) view of the `media_versions` list, which
uses the `sulu_media.show_version` and `sulu_media.delete_version` list item actions.

| Option  | Description                                                             |
|---------|-------------------------------------------------------------------------|
| locales | Defines which locales are available in the locale chooser of this form. |
