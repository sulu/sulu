This component shows selects for the available segments. If the component is used for a specific webspace, then only a
single Select is shown, otherwise it shows a separate select for every webspace.

The `value` is an object mapping the webspace keys to the selected segment keys, and `onChange` is called with the
complete new object. Webspaces without segments, or without view permission, are not shown. The `disabled` prop disables
all selects. The component reads the webspaces from the `webspaceStore`, which is filled with example data below.

```javascript
const webspaceStore = require('../../stores/webspaceStore').default;

webspaceStore.setWebspaces([
    {
        _permissions: {view: true},
        key: 'sulu',
        name: 'Sulu',
        segments: [
            {default: true, key: 'w', name: 'winter', title: 'Winter'},
            {default: false, key: 's', name: 'summer', title: 'Summer'},
        ],
    },
    {
        _permissions: {view: true},
        key: 'sulu_blog',
        name: 'Sulu Blog',
        segments: [
            {default: true, key: 'a', name: 'a', title: 'Variant A'},
            {default: false, key: 'b', name: 'b', title: 'Variant B'},
        ],
    },
    {
        _permissions: {view: true},
        key: 'sulu_doc',
        name: 'Sulu Doc',
        segments: [],
    },
]);

const [value, setValue] = React.useState({sulu: 's'});

<div>
    <h4>All webspaces</h4>
    <SegmentSelect onChange={setValue} value={value} />

    <h4>Single webspace</h4>
    <SegmentSelect onChange={setValue} value={value} webspace="sulu" />

    <h4>Disabled</h4>
    <SegmentSelect disabled={true} onChange={setValue} value={value} />

    <pre>{JSON.stringify(value)}</pre>
</div>
```
