The `MultiMediaSelection` component is based on the `MultiItemSelection` component and allows to assign multiple media
entries inside a form. It opens a [`MultiMediaSelectionOverlay`](#multimediaselectionoverlay) to add medias and loads
the selected ones itself, so it cannot be rendered in this documentation.

| Prop             | Description                                                                                     |
|------------------|-------------------------------------------------------------------------------------------------|
| `value`          | `{ids: Array<number>, displayOption: ?string}`.                                                 |
| `onChange`       | Called with the new value.                                                                      |
| `locale`         | Observable locale used to load the medias.                                                      |
| `displayOptions` | Display options the editor can choose from: `leftTop`, `top`, `rightTop`, `left`, `middle`, `right`, `leftBottom`, `bottom`, `rightBottom`. A button is shown only if the list is not empty. |
| `types`          | Restricts the selectable media types, e.g. `['image', 'video']`.                                |
| `classifications` | Restricts the selectable medias to these classifications, e.g. `['data_sheet']`.                |
| `sortable`       | Allows reordering of the items (default `true`).                                                |
| `disabled`       | Disables editing.                                                                               |
| `onItemClick`    | Called with the id and the media object of a clicked item.                                      |

```jsx static
<MultiMediaSelection
    displayOptions={['left', 'middle', 'right']}
    locale={locale}
    onChange={this.handleChange}
    onItemClick={this.handleItemClick}
    sortable={false}
    types={['image']}
    value={{ids: [1, 2], displayOption: 'middle'}}
/>
```
