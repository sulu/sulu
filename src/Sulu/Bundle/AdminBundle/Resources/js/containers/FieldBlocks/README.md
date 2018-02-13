This component makes use of the [`BlockCollection`](#blockcollection) component to render a few fields using the
`Renderer` component from the [`Form`](#form) component. There are several types passed, which will be selectable from
a dropdown and the schema defined in the type will be rendered in the blocks.

```javascript
const fieldRegistry = require('../Form/registries/FieldRegistry').default;

if (!fieldRegistry.has('text_line')) {
    fieldRegistry.add('text_line', () => (<input type="text" />));
}

initialState = {
	value: [
		{
    		type: 'default',
        	text: 'Test',
    	}
    ]
};

const onChange = (value) => setState({value});

const types = {
    default: {
    	title: 'Default',
        form: {
            text: {
                type: 'text_line',
            },
        },
    },
    extended: {
    	title: 'Extended',
        form: {
        	text1: {
            	type: 'text_line',
            },
            text2: {
            	type: 'text_line',
            }
        }
    }
};

<FieldBlocks types={types} value={state.value} onChange={onChange} />
```
