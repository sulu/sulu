The `Location` container is registered as field type `location` and lets an editor pick a geo location. It shows a small
[Leaflet](https://leafletjs.com/) map with a marker and the coordinates; the edit button opens a `LocationOverlay` where
the position can be chosen on a map and additional address data (title, street, number, code, town, country) can be
entered. The map loads its tiles from an external service, so it is not rendered in this documentation.

| Prop       | Description                                                                              |
|------------|------------------------------------------------------------------------------------------|
| `value`    | `{lat, long, zoom, title, street, number, code, town, country}` or `undefined`.           |
| `onChange` | Called with the new location, or `undefined` after it was removed.                       |
| `locale`   | Locale used by the overlay for the address search.                                       |
| `disabled` | Disables the edit button (default `false`).                                              |

```jsx static
<Location
    disabled={false}
    locale="en"
    onChange={this.handleChange}
    value={{
        code: '6850',
        country: 'AT',
        lat: 47.4125,
        long: 9.7417,
        number: '1',
        street: 'Hauptstrasse',
        title: 'Sulu',
        town: 'Dornbirn',
        zoom: 12,
    }}
/>
```

The `LocationOverlay` is used by the container and takes `open`, `value`, `locale`, `onClose` and
`onConfirm(value)`.
