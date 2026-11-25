The `IconCard` shows an icon, passed as an SVG string in `content`, with the `id` as its title. The `onClick`
callback receives the `id`.

```javascript
const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><circle cx="20" cy="20" r="16" fill="currentColor" /></svg>';

<IconCard content={svg} id="circle" isSelected={false} onClick={(id) => alert('Clicked ' + id)} />
```

With `isSelected` set the card is highlighted.

```javascript
const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><rect x="6" y="6" width="28" height="28" fill="currentColor" /></svg>';

<IconCard content={svg} id="square" isSelected={true} />
```
