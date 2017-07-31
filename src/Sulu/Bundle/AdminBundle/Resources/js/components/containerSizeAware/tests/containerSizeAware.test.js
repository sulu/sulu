/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import containerSizeAware from '../containerSizeAware';

test('Pass props to rendered component', () => {
    const Component = (props) => (<h1>{props.title}</h1>);
    const AwareComponent = containerSizeAware(Component);

    expect(render(<AwareComponent title="Test" />)).toMatchSnapshot();
});

test('Assign the passed class to the container', () => {
    const Component = () => (<h1>Component</h1>);
    const AwareComponent = containerSizeAware(Component, 'container-class');

    expect(render(<AwareComponent />)).toMatchSnapshot();
});

test('Pass the size of the container to the component via props', () => {
    window.requestAnimationFrame = (cb) => cb();

    class Component extends React.PureComponent {
        render = () => <h1>Component</h1>;
    }
    const AwareComponent = containerSizeAware(Component);

    const view = mount(<AwareComponent />);
    view.instance().readContainerDimensions({clientWidth: 500, clientHeight: 600});
    const component = view.find(Component);

    expect(component.props().containerWidth).toBe(500);
    expect(component.props().containerHeight).toBe(600);
});

test('The method containerDidMount should get called', () => {
    window.requestAnimationFrame = (cb) => cb();
    const funMock = jest.fn();

    class Component extends React.PureComponent {
        componentDidMount() {
            // container mounts after children
            expect(funMock).toHaveBeenCalledTimes(0);
        }
        containerDidMount = funMock;
        render = () => <h1>Component</h1>;
    }
    const AwareComponent = containerSizeAware(Component);

    mount(<AwareComponent />);

    expect(funMock).toHaveBeenCalledTimes(1);
});
