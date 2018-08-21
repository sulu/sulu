This component uses a [`Datagrid`](#datagrid) in an [`Overlay`](#overlay) to offer the possibility to select a few
resources. The `onConfirm` callback is called with the selected IDs as soon as the confirm button is clicked. The
`resourceKey` is passed as prop, and the component is responsible for creating a `DatagridStore` for loading the
required data. Otherwise it behaves as an Overlay within Sulu should.
