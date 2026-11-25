The `SingleMediaSelection` component is based on the `SingleItemSelection` component and allows to assign a single media entry inside a form.
It opens a [`SingleMediaSelectionOverlay`](#singlemediaselectionoverlay) to choose the media and loads the selected
one itself, so it cannot be rendered in this documentation.

| Prop             | Description                                                                                     |
|------------------|-------------------------------------------------------------------------------------------------|
| `value`          | `{id: ?number, displayOption: ?string}`.                                                        |
| `onChange`       | Called with the new value and the loaded media.                                                 |
| `locale`         | Observable locale used to load the media.                                                       |
| `displayOptions` | Display options the editor can choose from: `leftTop`, `top`, `rightTop`, `left`, `middle`, `right`, `leftBottom`, `bottom`, `rightBottom`. A button is shown only if the list is not empty. |
| `types`          | Restricts the selectable media types, e.g. `['image']`.                                         |
| `valid`          | Marks the selection as invalid if `false` (default `true`).                                     |
| `disabled`       | Disables editing.                                                                               |
| `onItemClick`    | Called with the id and the media object when the selected item is clicked.                      |
| `className`      | Additional CSS class.                                                                           |

```jsx static
<SingleMediaSelection
    displayOptions={['left', 'middle', 'right']}
    locale={locale}
    onChange={this.handleChange}
    types={['image']}
    valid={true}
    value={{id: 1, displayOption: 'middle'}}
/>
```
