// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import SingleItemSelection from '../SingleItemSelection';

test('Render with given children prop and with custom className', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const {container} =
        render(<SingleItemSelection className="test" leftButton={leftButton}>Test Item</SingleItemSelection>);
    expect(container).toMatchSnapshot();
});

test('Render with right button', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const rightButton = {
        icon: 'su-display-default',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection leftButton={leftButton} rightButton={rightButton}>Test Item</SingleItemSelection>
    );

    expect(container).toMatchSnapshot();
});

test('Render with right button with options', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const rightButton = {
        icon: 'su-display-default',
        onClick: jest.fn(),
        options: [
            {label: 'Test1', value: 'test-1'},
        ],
    };
    const {container} = render(
        <SingleItemSelection leftButton={leftButton} rightButton={rightButton}>Test Item</SingleItemSelection>
    );

    expect(container).toMatchSnapshot();
});

test('Render in disabled state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection
            disabled={true}
            leftButton={leftButton}
            onRemove={jest.fn()}
        >
            Test Item
        </SingleItemSelection>
    );

    expect(container).toMatchSnapshot();
});

test('Render in item-disabled state without remove button', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection
            allowRemoveWhileItemDisabled={false}
            itemDisabled={true}
            leftButton={leftButton}
            onRemove={jest.fn()}
        >
            Test Item
        </SingleItemSelection>
    );

    expect(container).toMatchSnapshot();
});

test('Render in item-disabled state with remove button', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection
            allowRemoveWhileItemDisabled={true}
            itemDisabled={true}
            leftButton={leftButton}
            onRemove={jest.fn()}
        >
            Test Item
        </SingleItemSelection>
    );

    expect(container).toMatchSnapshot();
});

test('Render in loading state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection leftButton={leftButton} loading={true}>Test Item</SingleItemSelection>
    );

    expect(container).toMatchSnapshot();
});

test('Render in loading state with no children', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection leftButton={leftButton} loading={true} />
    );

    expect(container).toMatchSnapshot();
});

test('Render in invalid state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection leftButton={leftButton} valid={false}>Test Item</SingleItemSelection>
    );

    expect(container).toMatchSnapshot();
});

test('Render with given onRemove prop', () => {
    const leftButton = {
        icon: 'su-page',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection leftButton={leftButton} onRemove={jest.fn()}>Test Item</SingleItemSelection>
    );

    expect(container).toMatchSnapshot();
});

test('Render with emptyText if no children have been passed', () => {
    const leftButton = {
        icon: 'su-page',
        onClick: jest.fn(),
    };
    const {container} = render(
        <SingleItemSelection emptyText="Nothing!" leftButton={leftButton} onRemove={jest.fn()} />
    );

    expect(container).toMatchSnapshot();
});

test('Call onClick callback if left button is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    render(<SingleItemSelection leftButton={leftButton} />);

    const button = screen.queryByLabelText('su-document');
    fireEvent.click(button);

    expect(leftButton.onClick).toBeCalledWith();
});

test('Call onClick callback with option value', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const rightButton = {
        icon: 'su-display-default',
        onClick: jest.fn(),
        options: [
            {
                label: 'Test1',
                value: 'test1',
            },
        ],
    };

    render(<SingleItemSelection leftButton={leftButton} rightButton={rightButton} />);

    const icon = screen.queryByLabelText('su-display-default');
    fireEvent.click(icon);
    const action = screen.queryByText(/Test1/);
    fireEvent.click(action);

    expect(rightButton.onClick).toBeCalledWith('test1');
});

test('Call onClick callback if right button is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const rightButton = {
        icon: 'su-display-default',
        onClick: jest.fn(),
    };

    render(<SingleItemSelection leftButton={leftButton} rightButton={rightButton} />);

    const icon = screen.queryByLabelText('su-display-default');
    fireEvent.click(icon);

    expect(rightButton.onClick).toBeCalledWith();
});

test('Call onItemClick callback should not be called if item is clicked but no id is given', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const itemClickSpy = jest.fn();
    render(<SingleItemSelection leftButton={leftButton} onItemClick={itemClickSpy}>item title</SingleItemSelection>);

    const item = screen.queryByText('item title');
    fireEvent.click(item);

    expect(itemClickSpy).not.toBeCalled();
});

test('Call onItemClick callback should be called if item is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const value = {id: 5};

    const itemClickSpy = jest.fn();
    render(
        <SingleItemSelection id={5} leftButton={leftButton} onItemClick={itemClickSpy} value={value}>
            item title
        </SingleItemSelection>
    );

    const item = screen.queryByText('item title');
    fireEvent.click(item);

    expect(itemClickSpy).toBeCalledWith(5, value);
});

test('Call onRemove callback if remove button is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const removeSpy = jest.fn();
    render(<SingleItemSelection leftButton={leftButton} onRemove={removeSpy} />);

    const icon = screen.queryByLabelText('su-trash-alt');
    fireEvent.click(icon);

    expect(removeSpy).toBeCalled();
});
