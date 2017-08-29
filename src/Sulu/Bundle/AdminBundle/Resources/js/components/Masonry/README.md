```
const MasonryItem = require('./MasonryItem').default;

function createChildren() {
    return [
        'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren.',
    ];
}

initialState = {
    show: true,
    items: [
        'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
    ],
};
<div>
    <button onClick={() => setState({ show: !state.show })}>Toggle visibility</button>
    <button onClick={() => setState({ items: state.items.concat(createChildren()) })}>Add items</button>

    {state.show &&
        <Masonry>
            {
                state.items.map((content, index) => {
                    return (
                        <MasonryItem key={index}>
                            <div>
                                {content}
                            </div>
                        </MasonryItem>
                    );
                })
            }
        </Masonry>
    }
</div>
```
