/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render} from 'enzyme';
import toolbarStore from '../stores/ToolbarStore';
import withToolbar from '../withToolbar';

jest.mock('../stores/ToolbarStore', () => {
    return {};
});

test('Pass props to rendered component', () => {
    toolbarStore.setItems = jest.fn();

    const Component = (props) => (<h1>{props.title}</h1>);
    const ComponentWithToolbar = withToolbar(Component, () => []);

    expect(render(<ComponentWithToolbar title="Test" />)).toMatchSnapshot();
});

test('Bind toolbar method to component instance', () => {
    toolbarStore.setItems = jest.fn();

    const Component = class Component extends React.Component {
        test = true;

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, function() {
        return [{
            title: 'Save',
            icon: 'save',
            enabled: this.test,
        }];
    });

    mount(<ComponentWithToolbar />);
    expect(toolbarStore.setItems).toBeCalledWith([{
        title: 'Save',
        icon: 'save',
        enabled: true,
    }]);
});
