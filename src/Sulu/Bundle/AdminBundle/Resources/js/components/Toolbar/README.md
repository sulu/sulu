The `Toolbar` component serves as container for `Controls`. The `Controls` component groups components which provide
the user interaction like `Button`, `Select` or `Dropdown`.

```javascript
const Toolbar = require('./Toolbar').default;

<Toolbar>
    <Toolbar.Controls>
        <Toolbar.Button onClick={() => null}>Test 1</Toolbar.Button>
        <Toolbar.Button onClick={() => null}>Test 2</Toolbar.Button>
    </Toolbar.Controls>
</Toolbar>
```

The space inside of the `Toolbar` will be divided fairly for all the `Controls` children. Inside of the `Controls`
component you can group the items by using the `Items` component.

```javascript
const Toolbar = require('./Toolbar').default;

<Toolbar>
    <Toolbar.Controls>
        <Toolbar.Button onClick={() => null}>Test 1</Toolbar.Button>
        <Toolbar.Toggler
            disabled={true}
            onClick={() => null}
            label="Toggler"
            value={true}
        />
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

The appearance of the `Toolbar` can be changed by passing the attribute `skin`. Available skins are `light` and `dark`. 

```javascript
const Toolbar = require('./Toolbar').default;

<Toolbar skin="dark">
    <Toolbar.Controls>
        <Toolbar.Toggler
            onClick={() => null}
            label="Toggler"
            value={false}
        />
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

The toolbar can also show error using a snackbar. The error will fill the entire toolbar and hide any controls
behind it.

```javascript
const Toolbar = require('./Toolbar').default;

initialState = {error: true};

<Toolbar>
    {state.error && <Toolbar.Snackbar onCloseClick={() => setState({error: false})} type="error" />}
    <Toolbar.Controls>
        <Toolbar.Button onClick={() => setState({error: true})}>Cause error</Toolbar.Button>
    </Toolbar.Controls>
</Toolbar>
```

In the same way it is possible to show success messages.

```javascript
const Toolbar = require('./Toolbar').default;

initialState = {success: false};

const buttonClick = () => {
    setState({success: true});
    setTimeout(() => setState({success: false}), 1500);
};

<Toolbar>
    {state.success && <Toolbar.Snackbar type="success" />}
    <Toolbar.Controls>
        <Toolbar.Button onClick={buttonClick}>Cause success</Toolbar.Button>
    </Toolbar.Controls>
</Toolbar>
```
