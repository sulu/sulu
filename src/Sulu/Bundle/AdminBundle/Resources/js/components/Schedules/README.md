The `Schedules` component allows to define different time schedules. This component enables the content manager to
create multiple blocks, that define when something should be activated. Currently a fixed time period and a weekly
recurring time schedule can be defined.

```javascript
const [value, setValue] = React.useState([]);

<>
    <Schedules onChange={setValue} value={value} />
    <p>Current value: {JSON.stringify(value)}</p>
</>
```
