The `ArrowMenu` is a component to stylize a list. Usage example of the `ArrowMenu` is the [`WebspaceSelect`](#webspaceselect) in the
SuluContentBundle.

Possible Children of this component are `Section`, for custom sections and the default `SingleSingleItemSection`.
The component `SingleSingleItemSection` can receive `Item` as children.

Important for this component is the render prop `anchorElement`.
This prop will be rendered into the component. Internally there is used an [`Popover`](#popover) to position the menu correctly.

Example with one section:

```javascript
const SingleItemSection = ArrowMenu.SingleItemSection;
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
        <SingleItemSection icon="su-webspace" title="Webspaces" value={state.value} onChange={handleChangeSection}>
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
        <SingleItemSection icon="su-webspace" title="Webspaces" value={state.value1} onChange={handleChangeSection1}>
            <Item value="sulu">Sulu</Item>
            <Item value="sulu_blog">Sulu Blog</Item>
            <Item value="sulu_doc">Sulu Doc</Item>
        </SingleItemSection>
        <SingleItemSection icon="su-checkmark" title="Columns" value={state.value2} onChange={handleChangeSection2}>
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
        <SingleItemSection icon="su-webspace" title="Webspaces" value={state.value1} onChange={handleChangeSection1}>
            <Item value="sulu">Sulu</Item>
            <Item value="sulu_blog">Sulu Blog</Item>
            <Item value="sulu_doc">Sulu Doc</Item>
        </SingleItemSection>
        <SingleItemSection icon="su-checkmark" title="Columns" value={state.value2} onChange={handleChangeSection2}>
            <Item value="title">Title</Item>
            <Item value="description">Description</Item>
        </SingleItemSection>
    </ArrowMenu>
</div>
```

```
<div>
    <div>
        <h3>Current values</h3>
        <ul>
            <li>Value 1: {state.value1}</li>
            <li>Value 2: {state.value2}</li>
        </ul>
    </div>
    
    <ArrowMenu open={state.open} onClose={handleClose} anchorElement={button}>
        <Section>
            <Action onClick={this.handle}>Column Options</Action>
        </Section>
    </ArrowMenu>
</div>
```