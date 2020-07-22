With `Tabs` you can easily switch between different views.

```javascript
const [selectedIndex, setSelectedIndex] = React.useState(0);

<Tabs selectedIndex={selectedIndex} onSelect={setSelectedIndex}>
    <Tabs.Tab>Cheeseburger</Tabs.Tab>
    <Tabs.Tab>Cupcakes</Tabs.Tab>
    <Tabs.Tab>Zombies</Tabs.Tab>
</Tabs>
```

There is also a `light` skin for tabs:

```javascript
const [selectedIndex, setSelectedIndex] = React.useState(0);

<Tabs selectedIndex={selectedIndex} onSelect={setSelectedIndex} skin={"compact"}>
    <Tabs.Tab>EN</Tabs.Tab>
    <Tabs.Tab>FR</Tabs.Tab>
    <Tabs.Tab>ES</Tabs.Tab>
    <Tabs.Tab>ZH</Tabs.Tab>
</Tabs>
```

If there are too many tabs, a dropdown at the end containing the hidden tabs will be rendered.

```javascript
const [selectedIndex, setSelectedIndex] = React.useState(0);

<Tabs selectedIndex={selectedIndex} onSelect={setSelectedIndex}>
    <Tabs.Tab>Paper</Tabs.Tab>
    <Tabs.Tab>Resolution</Tabs.Tab>
    <Tabs.Tab>Airport</Tabs.Tab>
    <Tabs.Tab>Assistance</Tabs.Tab>
    <Tabs.Tab>Recognition</Tabs.Tab>
    <Tabs.Tab>Thought</Tabs.Tab>
    <Tabs.Tab>Communication</Tabs.Tab>
    <Tabs.Tab>Promotion</Tabs.Tab>
    <Tabs.Tab>Preference</Tabs.Tab>
    <Tabs.Tab>Assignment</Tabs.Tab>
    <Tabs.Tab>Expression</Tabs.Tab>
    <Tabs.Tab>Writing</Tabs.Tab>
    <Tabs.Tab>Housing</Tabs.Tab>
    <Tabs.Tab>Security</Tabs.Tab>
    <Tabs.Tab>Category</Tabs.Tab>
    <Tabs.Tab>Lady</Tabs.Tab>
    <Tabs.Tab>Signature</Tabs.Tab>
    <Tabs.Tab>Meaning</Tabs.Tab>
    <Tabs.Tab>Stranger</Tabs.Tab>
</Tabs>
```
