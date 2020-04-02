The `ImageFocusPoint` component splits an image up in `9` areas from which `1` is selected and the remaining `8` are selectable. The purpose of this component is to easily select the important part of an image. The selection can then for example be considered when cropping the image.

```javascript
const [value, setValue] = React.useState({x: 1, y: 1});

handleChange = (value) => {
    setValue(value);
};

<div>
    <ImageFocusPoint
        value={value}
        onChange={handleChange}
        image="https://source.unsplash.com/random/600x400"
    />
    <div>Selection: {JSON.stringify(value)}</div>
</div>
```
