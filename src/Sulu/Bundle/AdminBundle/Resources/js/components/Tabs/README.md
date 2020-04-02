With `Tabs` you can easily switch between different views.

```javascript
const [selectedIndex, setSelectedIndex] = React.useState(0);

<Tabs selectedIndex={selectedIndex} onSelect={setSelectedIndex}>
    <Tabs.Tab>Cheeseburger</Tabs.Tab>
    <Tabs.Tab>Cupcakes</Tabs.Tab>
    <Tabs.Tab>Zombies</Tabs.Tab>
</Tabs>
```
