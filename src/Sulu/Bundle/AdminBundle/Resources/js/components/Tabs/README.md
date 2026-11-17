With `Tabs` you can easily switch between different views.

```javascript
const [selectedIndex, setSelectedIndex] = React.useState(0);

<Tabs selectedIndex={selectedIndex} onSelect={setSelectedIndex}>
    <Tabs.Tab>Cheeseburger</Tabs.Tab>
    <Tabs.Tab>Cupcakes</Tabs.Tab>
    <Tabs.Tab>Zombies</Tabs.Tab>
</Tabs>
```

If there are too many tabs, a dropdown containing the hidden tabs will be rendered at the end.

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

And the same with the `inline` type, which collapses the hidden tabs into the `...` dropdown on small screens too.

```javascript
const [selectedIndex, setSelectedIndex] = React.useState(0);

<Tabs type="inline" selectedIndex={selectedIndex} onSelect={setSelectedIndex}>
    <Tabs.Tab>#1</Tabs.Tab>
    <Tabs.Tab>#2</Tabs.Tab>
    <Tabs.Tab>#3</Tabs.Tab>
    <Tabs.Tab>#4</Tabs.Tab>
    <Tabs.Tab>#5</Tabs.Tab>
    <Tabs.Tab>#6</Tabs.Tab>
    <Tabs.Tab>#7</Tabs.Tab>
    <Tabs.Tab>#8</Tabs.Tab>
    <Tabs.Tab>#9</Tabs.Tab>
    <Tabs.Tab>#10</Tabs.Tab>
    <Tabs.Tab>#11</Tabs.Tab>
    <Tabs.Tab>#12</Tabs.Tab>
    <Tabs.Tab>#13</Tabs.Tab>
    <Tabs.Tab>#14</Tabs.Tab>
    <Tabs.Tab>#15</Tabs.Tab>
    <Tabs.Tab>#16</Tabs.Tab>
    <Tabs.Tab>#17</Tabs.Tab>
    <Tabs.Tab>#18</Tabs.Tab>
    <Tabs.Tab>#19</Tabs.Tab>
    <Tabs.Tab>#20</Tabs.Tab>
    <Tabs.Tab>#21</Tabs.Tab>
    <Tabs.Tab>#22</Tabs.Tab>
    <Tabs.Tab>#23</Tabs.Tab>
    <Tabs.Tab>#24</Tabs.Tab>
    <Tabs.Tab>#25</Tabs.Tab>
    <Tabs.Tab>#26</Tabs.Tab>
    <Tabs.Tab>#27</Tabs.Tab>
    <Tabs.Tab>#28</Tabs.Tab>
    <Tabs.Tab>#29</Tabs.Tab>
</Tabs>
```

The `type` prop selects the visual variant: `root` (default, main navigation of a view), `nested` (tabs inside a
tab) and `inline` (compact tabs inside a form). A `className` can be passed to style the container.

```javascript
const [selectedIndex, setSelectedIndex] = React.useState(0);

<div>
    {['root', 'nested', 'inline'].map((type) => (
        <Tabs key={type} type={type} selectedIndex={selectedIndex} onSelect={setSelectedIndex}>
            <Tabs.Tab>{type + ' 1'}</Tabs.Tab>
            <Tabs.Tab>{type + ' 2'}</Tabs.Tab>
            <Tabs.Tab>{type + ' 3'}</Tabs.Tab>
        </Tabs>
    ))}
</div>
```
