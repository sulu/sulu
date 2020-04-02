The `ArrowMenu` is a component to stylize a list. Usage example of the `ArrowMenu` is the [`WebspaceSelect`](#webspaceselect) in the
SuluPageBundle.

Possible Children of this component are `Section`, for custom sections and the default `SingleSingleItemSection`.
The component `SingleSingleItemSection` can receive `Item` as children.
The component `Action` can be used inside `Section` components.

Important for this component is the render prop `anchorElement`.
This prop will be rendered into the component. Internally there is used an [`Popover`](#popover) to position the menu correctly.

Example with one section:

```javascript
const SingleItemSection = ArrowMenu.SingleItemSection;
const Item = ArrowMenu.Item;

const [value, setValue] = React.useState('sulu');
const [open, setOpen] = React.useState(false);

const handleChangeSection = (value) => {
    setValue(value);
    setOpen(false);
};

const handleButtonClick = () => {
    setOpen(true);
};

const handleClose = () => {
    setOpen(false);
};

const button = (<button onClick={handleButtonClick}>{value}</button>);

<div>
    <div>
        <h3>Current value</h3>
        <ul>
            <li>Value: {value}</li>
        </ul>
    </div>
    
    <ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
        <SingleItemSection icon="su-webspace" title="Webspaces" value={value} onChange={handleChangeSection}>
            <Item value="sulu">Sulu</Item>
            <Item value="sulu_blog">Sulu Blog</Item>
            <Item value="sulu_doc">Sulu Doc</Item>
        </SingleItemSection>
    </ArrowMenu>
</div>
```

Example with two sections:

```javascript
const SingleItemSection = ArrowMenu.SingleItemSection;
const Item = ArrowMenu.Item;

const [value1, setValue1] = React.useState('sulu');
const [value2, setValue2] = React.useState(null);
const [open, setOpen] = React.useState(false);

const handleChangeSection1 = (value) => {
    setValue1(value);
    setOpen(false);
};

const handleChangeSection2 = (value) => {
    setValue2(value);
    setOpen(true);
};

const handleButtonClick = () => {
    setOpen(true);
};

const handleClose = () => {
    setOpen(false);
};

const button = (<button onClick={handleButtonClick}>Open ArrowMenu</button>);

<div>
    <div>
        <h3>Current values</h3>
        <ul>
            <li>Value 1: {value1}</li>
            <li>Value 2: {value2}</li>
        </ul>
    </div>
    
    <ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
        <SingleItemSection icon="su-webspace" title="Webspaces" value={value1} onChange={handleChangeSection1}>
            <Item value="sulu">Sulu</Item>
            <Item value="sulu_blog">Sulu Blog</Item>
            <Item value="sulu_doc">Sulu Doc</Item>
        </SingleItemSection>
        <SingleItemSection icon="su-checkmark" title="Columns" value={value2} onChange={handleChangeSection2}>
            <Item value="title">Title</Item>
            <Item value="description">Description</Item>
        </SingleItemSection>
    </ArrowMenu>
</div>
```

Example with an additional input sections:

```javascript
const SingleItemSection = ArrowMenu.SingleItemSection;
const Section = ArrowMenu.Section;
const Item = ArrowMenu.Item;

const [value1, setValue1] = React.useState('sulu');
const [value2, setValue2] = React.useState(null);
const [open, setOpen] = React.useState(false);

const handleChangeSection1 = (value) => {
    setValue1(value);
    setOpen(false);
};

const handleChangeSection2 = (value) => {
    setValue2(value);
    setOpen(true);
};

const handleButtonClick = () => {
    setOpen(true);
};

const handleClose = () => {
    setOpen(false);
};

const button = (<button onClick={handleButtonClick}>Open ArrowMenu</button>);

<div>
    <div>
        <h3>Current values</h3>
        <ul>
            <li>Value 1: {value1}</li>
            <li>Value 2: {value2}</li>
        </ul>
    </div>
    
    <ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
        <Section title="Search Section">
            <input type="text" />
        </Section>
        <SingleItemSection icon="su-webspace" title="Webspaces" value={value1} onChange={handleChangeSection1}>
            <Item value="sulu">Sulu</Item>
            <Item value="sulu_blog">Sulu Blog</Item>
            <Item value="sulu_doc">Sulu Doc</Item>
        </SingleItemSection>
        <SingleItemSection icon="su-checkmark" title="Columns" value={value2} onChange={handleChangeSection2}>
            <Item value="title">Title</Item>
            <Item value="description">Description</Item>
        </SingleItemSection>
    </ArrowMenu>
</div>
```

Example with actions:

```javascript

const Action = ArrowMenu.Action;
const Section = ArrowMenu.Section;

const [open, setOpen] = React.useState(false);

const handleAction1Click = (value) => {
    setOpen(false);
    alert('Action 1 clicked');
};

const handleAction2Click = (value) => {
    setOpen(true);
    alert('Action 2 clicked');
};

const handleAction3Click = (value) => {
    setOpen(true);
    alert('Action 3 clicked');
};

const handleButtonClick = () => {
    setOpen(true);
};

const handleClose = () => {
    setOpen(false);
};

const button = (<button onClick={handleButtonClick}>Open ArrowMenu</button>);

<ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
    <Section>
        <Action onClick={handleAction1Click}>Action 1</Action>
        <Action onClick={handleAction2Click}>Action 2</Action>
        <Action onClick={handleAction3Click}>Action 3</Action>
    </Section>
</ArrowMenu>
```
