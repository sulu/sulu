// @flow
import {fireEvent, render} from '@testing-library/react';
import React from 'react';
import withContainerSize from '../withContainerSize';

jest.mock('../../../utils/DOM/afterElementsRendered');

test('Pass props to rendered component', () => {
    class Component extends React.PureComponent<*> {
        render() {
            const {title} = this.props;

            return <h1>{title}</h1>;
        }
    }
    const WithSizeComponent = withContainerSize(Component);
    const {asFragment} = render(<WithSizeComponent title="Test" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Assign the passed class to the container', () => {
    class Component extends React.PureComponent<*> {
        render() {
            return <h1>Component</h1>;
        }
    }
    const WithSizeComponent = withContainerSize(Component, 'container-class');
    const {asFragment} = render(<WithSizeComponent />);

    expect(asFragment()).toMatchSnapshot();
});

test('Pass the size of the container to the component via props', () => {
    const componentRenderSpy = jest.fn();

    class Component extends React.PureComponent<any> {
        render = () => {
            componentRenderSpy(this.props);

            return <h1>Component</h1>;
        };
    }
    const WithSizeComponent = withContainerSize(Component);
    const {container} = render(<WithSizeComponent />);
    const wrapper = container.firstChild;

    if (!(wrapper instanceof HTMLElement)) {
        throw new Error('Expected wrapper element');
    }

    Object.defineProperty(wrapper, 'clientWidth', {
        value: 500,
        configurable: true,
    });
    Object.defineProperty(wrapper, 'clientHeight', {
        value: 600,
        configurable: true,
    });

    fireEvent(window, new Event('resize'));

    const props = componentRenderSpy.mock.calls[componentRenderSpy.mock.calls.length - 1][0];

    expect(props.containerWidth).toBe(500);
    expect(props.containerHeight).toBe(600);
});

test('The method containerDidMount should get called', () => {
    const funMock = jest.fn();

    class Component extends React.PureComponent<{}> {
        componentDidMount() {
            // container mounts after children
            expect(funMock).toHaveBeenCalledTimes(0);
        }
        containerDidMount = funMock;
        render = () => <h1>Component</h1>;
    }
    const WithSizeComponent = withContainerSize(Component);

    render(<WithSizeComponent />);

    expect(funMock).toHaveBeenCalledTimes(1);
});
