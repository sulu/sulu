The `Popover` component serves as a container to position other components on top of everything else. 
A good usecase example is the dropdown when a `Select` component is opened or the result list for the `Autocomplete` 
component. The `Popover` always expects to receive an anchor element as a prop, which can be any kind of HTML
element.

```javascript
const Menu = require('../Menu').default;
const Option = require('../Select').default.Option;

const [open, setOpen] = React.useState(false);
const [anchorElement, setAnchorElement] = React.useState(null);

const handleClose = () => {
    setOpen(false);
};

const handleOpen = (event) => {
    setOpen(true);
    setAnchorElement(event.currentTarget);
};

<div>
    <button onClick={handleOpen}>Pop me over bae!</button>
    <Popover
        open={open}
        anchorElement={anchorElement}
        onClose={handleClose}>
        {
            (setPopoverRef, styles) => (
                <Menu menuRef={setPopoverRef} style={styles}>
                    <Option>Cornelius</Option>
                    <Option>Handsome</Option>
                    <Option>Jakob</Option>
                </Menu>
            )
        }
    </Popover>
</div>
```

The `backdrop` prop (default `true`) toggles the transparent backdrop that catches outside clicks. The
`horizontalAnchorMode` (`left` or `center`) aligns the popover to the anchor, `horizontalOffset` and `verticalOffset`
move it in pixels, and `centerChildElement` names the child that is centered on the anchor. `popoverChildRef` receives
the popover node.

```javascript
const [open, setOpen] = React.useState(false);
const [anchorElement, setAnchorElement] = React.useState(null);

<div>
    <button onClick={(event) => {setOpen(true); setAnchorElement(event.currentTarget);}}>Open centered popover</button>
    <Popover
        anchorElement={anchorElement}
        backdrop={false}
        horizontalAnchorMode="center"
        horizontalOffset={10}
        onClose={() => setOpen(false)}
        open={open}
        verticalOffset={5}
    >
        {
            (setPopoverRef, styles) => (
                <Menu menuRef={setPopoverRef} style={styles}>
                    <li onClick={() => setOpen(false)}>Close me</li>
                </Menu>
            )
        }
    </Popover>
</div>
```
