// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import withContainerSize from '../withContainerSize';

jest.mock('../../../utils/DOM/afterElementsRendered');

test('Pass props to rendered component', () => {
    let passedProps: any = {};
    class Component extends React.PureComponent<any> {
        render = () => {
            passedProps = this.props;

            return <h1>{this.props.title}</h1>;
        };
    }

    const WithSizeComponent = withContainerSize(Component);
    const {asFragment} = render(<WithSizeComponent title="Test" />);

    expect(passedProps.title).toEqual('Test');
    expect(screen.getByText('Test')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Assign the passed class to the container', () => {
    class Component extends React.PureComponent<{}> {
        render = () => <h1>Component</h1>;
    }

    const WithSizeComponent = withContainerSize(Component, 'container-class');
    render(<WithSizeComponent />);

    const componentNode = screen.getByText('Component');
    if (!componentNode.parentElement) {
        throw new Error('Expected parent container to exist');
    }

    expect(componentNode.parentElement).toHaveClass('container-class');
});

test('Pass the size of the container to the component via props', () => {
    const originalClientWidth = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'clientWidth');
    const originalClientHeight = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'clientHeight');
    Object.defineProperty(HTMLElement.prototype, 'clientWidth', {
        configurable: true,
        get() {
            return 500;
        },
    });
    Object.defineProperty(HTMLElement.prototype, 'clientHeight', {
        configurable: true,
        get() {
            return 600;
        },
    });

    let passedProps: any = {};
    class Component extends React.PureComponent<any> {
        render = () => {
            passedProps = this.props;

            return <h1>Component</h1>;
        };
    }

    const WithSizeComponent = withContainerSize(Component);
    render(<WithSizeComponent />);

    expect(passedProps.containerWidth).toBe(500);
    expect(passedProps.containerHeight).toBe(600);

    if (originalClientWidth) {
        Object.defineProperty(HTMLElement.prototype, 'clientWidth', originalClientWidth);
    }
    if (originalClientHeight) {
        Object.defineProperty(HTMLElement.prototype, 'clientHeight', originalClientHeight);
    }
});

test('The method containerDidMount should get called', () => {
    const funMock = jest.fn();

    class Component extends React.PureComponent<{}> {
        containerDidMount = funMock;

        render = () => <h1>Component</h1>;
    }
    const WithSizeComponent = withContainerSize(Component);
    const {unmount} = render(<WithSizeComponent />);

    expect(funMock).toHaveBeenCalledTimes(1);

    unmount();
});
