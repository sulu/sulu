The `Toolbar` is a powerful component to interact with the data inside of a view and provide different actions. It comes
with built-in support for the sub-components `Button`, `Select` and `Dropdown`.

```
const Toolbar = require('./Toolbar').default;

<Toolbar>
    <Toolbar.Controls>
        <Toolbar.Button onClick={() => null}>Test 1</Toolbar.Button>
        <Toolbar.Button onClick={() => null}>Test 2</Toolbar.Button>
    </Toolbar.Controls>
</Toolbar>
```

The space inside of the `Toolbar` will be divided fairly for all the `Controls` children. To group items inside of the
`Controls` component you can group the items by using the `Items` component.

```
const Toolbar = require('./Toolbar').default;

<Toolbar>
    <Toolbar.Controls>
        <Toolbar.Button onClick={() => null}>Test 1</Toolbar.Button>
        <Toolbar.Items>
            <Toolbar.Button onClick={() => null}>Test 2</Toolbar.Button>
            <Toolbar.Dropdown
                label="Chose an option" 
                onClick={() => null}
                options={[
                    {
                        label: 'An option',
                        onClick: () => null,
                    },
                ]}
            />
        </Toolbar.Items>
    </Toolbar.Controls>
    <Toolbar.Controls>
        <Toolbar.Select 
            label="Chose an option" 
            onClick={() => null}
            options={[
                {
                    value: 1,
                    label: 'An option',
                },
            ]}
        />
    </Toolbar.Controls>
</Toolbar>
```
