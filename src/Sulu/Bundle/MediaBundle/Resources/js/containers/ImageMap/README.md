The `ImageMap` container is a combination of a `SingleMediaSelection` and a form. It allows defining an arbitrary amount
of hotspot areas on the selected image and assigning values to each of those hotspots. A hotspot can be either of type
`Rectangle`, `Circle` or `Point`. It's up to the developer which fields are available to be filled out by content editors.

It needs a `SingleMediaSelection` with a loaded media and therefore cannot be rendered in this documentation.

| Prop                | Description                                                                                       |
|---------------------|---------------------------------------------------------------------------------------------------|
| `value`             | `{imageId, hotspots}`, each hotspot is `{type, hotspot: {type: 'point' / 'circle' / 'rectangle', ...}}`. |
| `onChange`          | Called with the new value.                                                                        |
| `onFinish`          | Called when a change is finished, e.g. after dragging a hotspot.                                  |
| `locale`            | Observable locale used to load the media.                                                         |
| `types`             | Map of the available hotspot form types and their titles, e.g. `{default: 'Default'}`.            |
| `defaultFormType`   | Type which is preselected for a new hotspot.                                                      |
| `renderHotspotForm` | `(value, type, index) => Node` renders the form fields of a single hotspot.                       |
| `disabled`          | Disables editing.                                                                                 |
| `valid`             | Marks the selection as invalid if `false`.                                                        |

```jsx static
<ImageMap
    defaultFormType="default"
    disabled={false}
    locale={locale}
    onChange={this.handleChange}
    onFinish={this.handleFinish}
    renderHotspotForm={(value, type, index) => <HotspotForm value={value} type={type} index={index} />}
    types={{default: 'Default', special: 'Special'}}
    valid={true}
    value={{imageId: 1, hotspots: [{type: 'default', hotspot: {type: 'point', left: 10, top: 10}}]}}
/>
```
