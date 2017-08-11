/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import toolbarStorePool from '../stores/ToolbarStorePool';
import withToolbar from '../withToolbar';

jest.mock('../stores/ToolbarStorePool', () => ({
    setToolbarConfig: jest.fn(),
}));

test('Pass props to rendered component', () => {
    const Component = (props) => (<h1>{props.title}</h1>);
    const ComponentWithToolbar = withToolbar(Component, () => {});

    expect(render(<ComponentWithToolbar title="Test" />)).toMatchSnapshot();
});

test('Bind toolbar method to component instance', () => {
    const storeKey = 'testKey';

    const Component = class Component extends React.Component {
        test = true;

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, function() {
        return {
            items: [
                {
                    label: 'Save',
                    icon: 'save',
                    disabled: true,
                },
            ],
        };
    }, storeKey);

    mount(<ComponentWithToolbar />);
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(storeKey, {
        items: [
            {
                label: 'Save',
                icon: 'save',
                disabled: true,
            },
        ],
    });
});
