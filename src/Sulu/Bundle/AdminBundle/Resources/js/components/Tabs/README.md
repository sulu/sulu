With `Tabs` you can easily switch between different views.

```
initialState = {
    selectedIndex: 0,
};

const handleSelect = (selectedIndex) => {
    setState({
        selectedIndex: selectedIndex
    });
};

<Tabs selectedIndex={state.selectedIndex} onSelect={handleSelect}>
    <Tabs.Tab>Cheeseburger</Tabs.Tab>
    <Tabs.Tab>Cupcakes</Tabs.Tab>
    <Tabs.Tab>Zombies</Tabs.Tab>
</Tabs>
```
