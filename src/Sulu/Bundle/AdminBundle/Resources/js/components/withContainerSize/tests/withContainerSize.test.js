/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import withContainerSize from '../withContainerSize';

jest.mock('../../../utils/DOM/afterElementsRendered');

test('Pass props to rendered component', () => {
    const Component = (props) => (<h1>{props.title}</h1>);
    const WithSizeComponent = withContainerSize(Component);

    expect(render(<WithSizeComponent title="Test" />)).toMatchSnapshot();
});

test('Assign the passed class to the container', () => {
    const Component = () => (<h1>Component</h1>);
    const WithSizeComponent = withContainerSize(Component, 'container-class');

    expect(render(<WithSizeComponent />)).toMatchSnapshot();
});

test('Pass the size of the container to the component via props', () => {
    class Component extends React.PureComponent {
        render = () => <h1>Component</h1>;
    }
    const WithSizeComponent = withContainerSize(Component);

    const view = mount(<WithSizeComponent />);
    view.instance().readContainerDimensions({clientWidth: 500, clientHeight: 600});
    view.update();
    const component = view.find(Component);

    expect(component.props().containerWidth).toBe(500);
    expect(component.props().containerHeight).toBe(600);
});

test('The method containerDidMount should get called', () => {
    const funMock = jest.fn();

    class Component extends React.PureComponent {
        componentDidMount() {
            // container mounts after children
            expect(funMock).toHaveBeenCalledTimes(0);
        }
        containerDidMount = funMock;
        render = () => <h1>Component</h1>;
    }
    const WithSizeComponent = withContainerSize(Component);

    mount(<WithSizeComponent />);

    expect(funMock).toHaveBeenCalledTimes(1);
});
