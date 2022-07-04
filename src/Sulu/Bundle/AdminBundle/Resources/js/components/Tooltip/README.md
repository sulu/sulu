This component displays a tooltip if its content is hovered or focused:

```javascript
import Icon from '../Icon';

<Tooltip label="Some hover text" aria-label="Some hover text">
    Hover to see the tooltip
</Tooltip>
```

The tooltip itself is rendered on the root body element to avoid z-index problems, as this means
it is out of context it is rendered as `aria-hidden`.

For accessibility reasons your button or element should define the `aria-label` attribute.

A button with a tooltip should be wrapped inside the tooltip so focus on the button will also
show the tooltip:

```javascript
import Icon from '../Icon';
const buttonStyle = {
    background: 'none',
    border: 'none',
    cursor: 'pointer',
};

<div style={{display: 'flex', gap: '20px'}}>
    <Tooltip label="Copy">
        <button type="button" onClick={() => {alert("Copy")}} aria-label="Copy" style={buttonStyle}>
            <Icon name="su-copy" />
        </button>
    </Tooltip>

    <Tooltip label="Duplicate">
        <button type="button" onClick={() => {alert("Duplicate")}} aria-label="Duplicate" style={buttonStyle}>
            <Icon name="su-duplicate" />
        </button>
    </Tooltip>

    <Tooltip label="Cut">
        <button type="button" onClick={() => {alert("Cut")}} aria-label="Cut" style={buttonStyle}>
            <Icon name="su-cut" />
        </button>
    </Tooltip>

    <Tooltip label="Delete">
        <button type="button" onClick={() => {alert("Delete")}} aria-label="Delete" style={buttonStyle}>
            <Icon name="su-trash-alt" />
        </button>
    </Tooltip>
</div>
```
