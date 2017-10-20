The `Tree` component consists out of six parts of 5 Parts: `Header`, `Body`, `Node`, `Element`, `Children`.
All of them has to be imported in order to build a tree.

```
const Header = Tree.Header;
const Body = Tree.Body;
const Node = Tree.Node;
const Element = Tree.Element;
const Children = Tree.Children;

const buttons = [{
    icon: 'heart',
    onClick: (rowId) => {
        state.rows[rowId] = state.rows[rowId].map((cell) => 'You are still awesome 😘');
        const newRows = state.rows;

        setState({
            rows: newRows,
        })
    },
}];

<Tree buttons={buttons} selectMode={'multiple'}>
    <Header>Title</Header>

    <Body>
        <Node>
            <Element>Test 1</Element>

            <Children>
                <Node>
                    <Element>Test 1.1</Element>
                </Node>

                <Node>
                    <Element>Test 1.2</Element>
                </Node>
            </Children>
        </Node>
        
        <Node>
            <Element>Test 2</Element>

            <Children>
                <Node>
                    <Element>Test 2.1</Element>
                    
                    <Children>
                        <Node>
                            <Element>Test 2.1.1</Element>
                        </Node>
        
                        <Node>
                            <Element>Test 2.1.2</Element>
                        </Node>
        
                        <Node>
                            <Element>Test 2.1.3</Element>
                        </Node>
                    </Children>
                </Node>

                <Node>
                    <Element>Test 2.2</Element>
                </Node>
            </Children>
        </Node>
    </Body>
</Tree>
```
