/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {mount, render} from 'enzyme';
import toolbarStorePool, {DEFAULT_STORE_KEY} from '../stores/ToolbarStorePool';
import withToolbar from '../withToolbar';

jest.mock('../stores/ToolbarStorePool', () => ({
    setToolbarConfig: jest.fn(),
}));

test('Pass props to rendered component', () => {
    const Component = class Component extends React.Component {
        render() {
            return <h1>{this.props.title}</h1>;
        }
    };

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
                    icon: 'su-save',
                    disabled: this.test,
                },
            ],
        };
    }, storeKey);

    mount(<ComponentWithToolbar />);
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(storeKey, {
        items: [
            {
                label: 'Save',
                icon: 'su-save',
                disabled: true,
            },
        ],
    });
});

test('Call life-cycle events of rendered component', () => {
    const Component = class Component extends React.Component {
        componentWillUnmount = jest.fn();
        render = jest.fn();
    };

    const ComponentWithToolbar = withToolbar(Component, () => {});

    const component = mount(<ComponentWithToolbar />);
    expect(component.instance().render).toBeCalled();

    const componentWillUnmount = component.instance().componentWillUnmount;
    component.unmount();
    expect(componentWillUnmount).toBeCalled();
});

test('Recall toolbar-function when changing observable', () => {
    const Component = class Component extends React.Component {
        @observable test = true;

        render() {
            return <h1>Test</h1>;
        }
    };

    const ComponentWithToolbar = withToolbar(Component, function() {
        return {disableAll: this.test};
    });

    const component = mount(<ComponentWithToolbar />);

    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(DEFAULT_STORE_KEY, {
        disableAll: true,
    });

    component.instance().test = false;
    expect(toolbarStorePool.setToolbarConfig).toBeCalledWith(DEFAULT_STORE_KEY, {
        disableAll: false,
    });
});
