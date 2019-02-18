The `MultiSelection` can be used to make any kind of selection. For assigning an entity a
[`ListOverlay`](#listoverlay) is used. What kind of entities are available is defined by the `resourceKey` prop
of the component. The `displayProperties` prop allows to define which properties of the loaded objects should be used
to build the items in the selection list. Finally the `adapter` option allows to define which list adapter should be
used in the overlay.

Like most other fields the `value` prop and the `onChange` callback are used to define the current value, in this case
the IDs of the assigned entities. The `title` attribute can be used to define the title of the Overlay, whereby `icon`
and `label` are used in the head of the [`MultiItemSelection`](#multiitemselection) used by this component.
