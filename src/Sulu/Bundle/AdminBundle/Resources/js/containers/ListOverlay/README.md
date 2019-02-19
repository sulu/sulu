This component uses a [`List`](#list) in an [`Overlay`](#overlay) to offer the possibility to select a few resources.
The `onConfirm` callback is called with the selected IDs as soon as the confirm button is clicked. This component also
gets a `ListStore` passed, and is not responsible for its creation. The `clearSelectionOnClose` prop defines whether or
not the selection of the `List` should be cleared after the `Overlay` has been closed.

This component mainly serves as base for the [`SingleListOverlay`](#singlelistoverlay) and
[`MultiListOverlay`](#multilistoverlay) components.
