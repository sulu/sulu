// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('Call onClick callback if left button is clicked', async() => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    render(<SingleItemSelection leftButton={leftButton} />);

    const button = screen.queryByLabelText('su-document');
    await userEvent.click(button);

    expect(leftButton.onClick).toBeCalledWith();
});

test('Call onClick callback with option value', async() => {
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
    await userEvent.click(icon);
    const action = screen.queryByText(/Test1/);
    await userEvent.click(action);

    expect(rightButton.onClick).toBeCalledWith('test1');
});

test('Call onClick callback if right button is clicked', async() => {
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
    await userEvent.click(icon);

    expect(rightButton.onClick).toBeCalledWith();
});

test('Call onItemClick callback should not be called if item is clicked but no id is given', async() => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const itemClickSpy = jest.fn();
    render(<SingleItemSelection leftButton={leftButton} onItemClick={itemClickSpy}>item title</SingleItemSelection>);

    const item = screen.queryByText('item title');
    await userEvent.click(item);

    expect(itemClickSpy).not.toBeCalled();
});

test('Call onItemClick callback should be called if item is clicked', async() => {
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
    await userEvent.click(item);

    expect(itemClickSpy).toBeCalledWith(5, value);
});

test('Call onRemove callback if remove button is clicked', async() => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const removeSpy = jest.fn();
    render(<SingleItemSelection leftButton={leftButton} onRemove={removeSpy} />);

    const icon = screen.queryByLabelText('su-trash-alt');
    await userEvent.click(icon);

    expect(removeSpy).toBeCalled();
});
