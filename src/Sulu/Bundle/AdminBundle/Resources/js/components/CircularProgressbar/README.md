A circular progress indicator component.

```javascript
const containerStyles = {
    display: 'flex',
    alignItems: 'center',
};

const itemStyle = {
    marginRight: 30,
};

<div style={containerStyles}>
    <div style={itemStyle}>
        <CircularProgressbar percentage={60} />
    </div>
    <div style={itemStyle}>
        <CircularProgressbar percentage={75} hidePercentageText={true} />
    </div>
    <div style={itemStyle}>
        <CircularProgressbar percentage={60} hidePercentageText={true} size={40} />
    </div>
</div>
```
