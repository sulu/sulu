The grid system consists out of 3 components: `Grid`, `Section` and `Item`. The `Grid` component is the overall
container and should always be used if you want to create a grid. The `Item` subcomponent can mostly be treated like a
column in an usual grid system. If the sum of the item sizes exceeds 12 the next item will start in a new line.
A `Section` serves as a container for `Item` components. It has the same `props` as the `Item` but is different in terms
of style. The `Section` has no padding on the right side like the `Item` does but instead has a margin on the bottom
side.

```javascript
import Input from '../Input';

const boxStyles = {
    width: `100%`,
    height: 30,
    margin: 2,
};

<Grid>
    <Grid.Section size={4}>
        <Grid.Item size={12}>
            <div style={{width: `100%`, height: 200, backgroundColor: '#bada55'}} />
        </Grid.Item>
    </Grid.Section>
    <Grid.Section size={8}>
        <Grid.Item size={4} spaceAfter={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={2}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={2}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={8}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
    </Grid.Section>
    <Grid.Section size={12}>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
        <Grid.Item size={4}>
            <div style={boxStyles}>
                <Input />
            </div>
        </Grid.Item>
    </Grid.Section>
</Grid>
```
