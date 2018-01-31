Example:

```javascript
const ItemSection = ArrowMenu.ItemSection;
const Item = ArrowMenu.Item;

initialState = {
    value: 'sulu',
    open: false,
};

const handleChangeSection = (value) => {
    setState(() => ({
        value: value,
        open: false,
    }));
};

const handleButtonClick = () => {
    setState(() => ({
        open: true,
    }));
};

const handleClose = () => {
    setState(() => ({
        open: false,
    }));
};

const button = (<button onClick={handleButtonClick}>{state.value}</button>);

<div>
    <div>
        <h3>Current value</h3>
        <ul>
            <li>Value: {state.value}</li>
        </ul>
    </div>
    
    <ArrowMenu open={state.open} onClose={handleClose} anchorElement={button}>
        <ItemSection icon="dot-circle-o" title="Webspaces" value={state.value} onChange={handleChangeSection}>
            <Item value="sulu">Sulu</Item>
            <Item value="sulu_blog">Sulu Blog</Item>
            <Item value="sulu_doc">Sulu Doc</Item>
        </ItemSection>
    </ArrowMenu>
</div>
```

Example with two sections:

```javascript
const ItemSection = ArrowMenu.ItemSection;
const Item = ArrowMenu.Item;

initialState = {
    value1: 'sulu',
    value2: null,
    open: false,
};

const handleChangeSection1 = (value) => {
    setState(() => ({
        value1: value,
        open: false,
    }));
};

const handleChangeSection2 = (value) => {
    setState(() => ({
        value2: value,
        open: true,
    }));
};

const handleButtonClick = () => {
    setState(() => ({
        open: true,
    }));
};

const handleClose = () => {
    setState(() => ({
        open: false,
    }));
};

const button = (<button onClick={handleButtonClick}>Open ArrowMenu</button>);


<div>
    <div>
        <h3>Current values</h3>
        <ul>
            <li>Value 1: {state.value1}</li>
            <li>Value 2: {state.value2}</li>
        </ul>
    </div>
    
    
    <ArrowMenu open={state.open} onClose={handleClose} anchorElement={button}>
        <ItemSection icon="dot-circle-o" title="Webspaces" value={state.value1} onChange={handleChangeSection1}>
            <Item value="sulu">Sulu</Item>
            <Item value="sulu_blog">Sulu Blog</Item>
            <Item value="sulu_doc">Sulu Doc</Item>
        </ItemSection>
        <ItemSection icon="check" title="Columns" value={state.value2} onChange={handleChangeSection2}>
            <Item value="title">Title</Item>
            <Item value="description">Description</Item>
        </ItemSection>
    </ArrowMenu>
</div>
```

Example with an additional input sections:

```javascript
const ItemSection = ArrowMenu.ItemSection;
const Section = ArrowMenu.Section;
const Item = ArrowMenu.Item;

initialState = {
    value1: 'sulu',
    value2: null,
    open: false,
};

const handleChangeSection1 = (value) => {
    setState(() => ({
        value1: value,
        open: false,
    }));
};

const handleChangeSection2 = (value) => {
    setState(() => ({
        value2: value,
        open: true,
    }));
};

const handleButtonClick = () => {
    setState(() => ({
        open: true,
    }));
};

const handleClose = () => {
    setState(() => ({
        open: false,
    }));
};

const button = (<button onClick={handleButtonClick}>Open ArrowMenu</button>);


<div>
    <div>
        <h3>Current values</h3>
        <ul>
            <li>Value 1: {state.value1}</li>
            <li>Value 2: {state.value2}</li>
        </ul>
    </div>
    
    <ArrowMenu open={state.open} onClose={handleClose} anchorElement={button}>
        <Section title="Search Section">
            <input type="text" />
        </Section>
        <ItemSection icon="dot-circle-o" title="Webspaces" value={state.value1} onChange={handleChangeSection1}>
            <Item value="sulu">Sulu</Item>
            <Item value="sulu_blog">Sulu Blog</Item>
            <Item value="sulu_doc">Sulu Doc</Item>
        </ItemSection>
        <ItemSection icon="check" title="Columns" value={state.value2} onChange={handleChangeSection2}>
            <Item value="title">Title</Item>
            <Item value="description">Description</Item>
        </ItemSection>
    </ArrowMenu>
</div>
```