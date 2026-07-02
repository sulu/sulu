/* eslint-disable testing-library/no-container */
// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import MultiItemSelection from '../MultiItemSelection';

jest.mock('react-sortable-hoc', () => ({
    SortableContainer: (Component) => Component,
    SortableElement: (Component) => Component,
    SortableHandle: (Component) => Component,
}));

function renderMultiItemSelection(props = {}, children = undefined) {
    return render(
        <MultiItemSelection {...props}>
            {children}
        </MultiItemSelection>
    );
}

function getChildren() {
    return [
        <MultiItemSelection.Item id="1" index={1} key="1">
            Child 1
        </MultiItemSelection.Item>,
        <MultiItemSelection.Item id="2" index={2} key="2" onEdit={jest.fn()} onRemove={jest.fn()}>
            Child 2
        </MultiItemSelection.Item>,
        <MultiItemSelection.Item disabled={true} id="3" index={3} key="3" onEdit={jest.fn()} onRemove={jest.fn()}>
            Child 3 (disabled)
        </MultiItemSelection.Item>,
        <MultiItemSelection.Item
            allowRemoveWhileDisabled={true}
            disabled={true}
            id="4"
            index={4}
            key="4"
            onEdit={jest.fn()}
            onRemove={jest.fn()}
        >
            Child 4 (disabled with remove button)
        </MultiItemSelection.Item>,
    ];
}

function getRequiredElement(container, selector) {
    const element = container.querySelector(selector);

    if (!element) {
        throw new Error(`Expected element for selector "${selector}"`);
    }

    return element;
}

test('Render an empty MultiItemSelection', () => {
    const {asFragment} = renderMultiItemSelection({label: 'I am empty'});

    expect(asFragment()).toMatchSnapshot();
});

test('Render an MultiItemSelection with children', () => {
    const {asFragment} = renderMultiItemSelection({label: 'I have children'}, getChildren());

    expect(asFragment()).toMatchSnapshot();
});

test('Render a disabled MultiItemSelection with children', () => {
    const {asFragment} = renderMultiItemSelection(
        {disabled: true, label: 'I am disabled'},
        [
            <MultiItemSelection.Item id="1" index={1} key="1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="2" index={2} key="2">Child 2</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="3" index={3} key="3">Child 3</MultiItemSelection.Item>,
        ]
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a button on the right with options and a value', () => {
    const rightButton = {label: 'Test', onClick: jest.fn(), options: [{label: 'Test1', value: 'test-1'}]};
    const {asFragment} = renderMultiItemSelection(
        {rightButton},
        [
            <MultiItemSelection.Item id="1" index={1} key="1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="2" index={2} key="2">Child 2</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="3" index={3} key="3">Child 3</MultiItemSelection.Item>,
        ]
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a button on the right with options including icons and a value', () => {
    const rightButton = {
        label: 'Test',
        onClick: jest.fn(),
        options: [{icon: 'su-default', label: 'Test1', value: 'test-1'}],
    };
    const {asFragment} = renderMultiItemSelection(
        {rightButton},
        [
            <MultiItemSelection.Item id="1" index={1} key="1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="2" index={2} key="2">Child 2</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="3" index={3} key="3">Child 3</MultiItemSelection.Item>,
        ]
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a not sortable MultiItemSelection with children', () => {
    const {asFragment} = renderMultiItemSelection(
        {label: 'I have children', sortable: false},
        [
            <MultiItemSelection.Item id="1" index={1} key="1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="2" index={2} key="2">Child 2</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="3" index={3} key="3">Child 3</MultiItemSelection.Item>,
        ]
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render an MultiItemSelection while loading', () => {
    const {asFragment} = renderMultiItemSelection({label: 'I am loading', loading: true});

    expect(asFragment()).toMatchSnapshot();
});

test('Clicking the left and right button inside the header should call the right handler', async() => {
    const user = userEvent.setup();
    const leftClickHandler = jest.fn();
    const rightClickHandler = jest.fn();
    const leftButtonConfig = {
        icon: 'su-plus',
        onClick: leftClickHandler,
    };
    const rightButtonConfig = {
        icon: 'fa-gear',
        onClick: rightClickHandler,
    };
    const {asFragment, container} = renderMultiItemSelection(
        {
            label: 'I have handler',
            leftButton: leftButtonConfig,
            rightButton: rightButtonConfig,
        },
        [
            <MultiItemSelection.Item id="1" index={1} key="1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="2" index={2} key="2">Child 2</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="3" index={3} key="3">Child 3</MultiItemSelection.Item>,
        ]
    );

    expect(asFragment()).toMatchSnapshot();

    await user.click(getRequiredElement(container, '.button.left'));
    expect(leftClickHandler).toHaveBeenCalled();

    await user.click(getRequiredElement(container, '.button.right'));
    expect(rightClickHandler).toHaveBeenCalled();
});

test('Clicking the left button inside the header should call the right handler after choosing an option', async() => {
    const user = userEvent.setup();
    const leftClickHandler = jest.fn();
    const leftButtonConfig = {
        icon: 'su-plus',
        onClick: leftClickHandler,
        options: [
            {
                label: 'Test1',
                value: 'test1',
            },
            {
                label: 'Test2',
                value: 'test2',
            },
        ],
    };

    const {container} = renderMultiItemSelection(
        {
            label: 'I have handler',
            leftButton: leftButtonConfig,
        },
        [
            <MultiItemSelection.Item id="1" index={1} key="1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="2" index={2} key="2">Child 2</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="3" index={3} key="3">Child 3</MultiItemSelection.Item>,
        ]
    );

    await user.click(getRequiredElement(container, '.button.left'));
    await user.click(screen.getByRole('button', {name: 'Test1'}));
    expect(leftClickHandler).toHaveBeenCalledWith('test1');

    await user.click(getRequiredElement(container, '.button.left'));
    await user.click(screen.getByRole('button', {name: 'Test2'}));
    expect(leftClickHandler).toHaveBeenCalledWith('test2');
});

test('Clicking on the remove button inside an item should call the remove handler on the parent component', async() => {
    const user = userEvent.setup();
    const removeHandler = jest.fn();
    const clickedItemId = 1;
    renderMultiItemSelection(
        {
            label: 'I have handler',
            onItemRemove: removeHandler,
        },
        [
            <MultiItemSelection.Item id={clickedItemId} index={1} key="1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="2" index={2} key="2">Child 2</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="3" index={3} key="3">Child 3</MultiItemSelection.Item>,
        ]
    );

    const removeButtons = screen.getAllByLabelText('su-trash-alt');
    await user.click(removeButtons[0].closest('button'));
    expect(removeHandler).toHaveBeenCalledWith(clickedItemId);
});

test('Clicking on the edit button inside an item should call the edit handler on the parent component', async() => {
    const user = userEvent.setup();
    const editHandler = jest.fn();
    const clickedItemId = 1;
    renderMultiItemSelection(
        {
            label: 'I have handler',
            onItemEdit: editHandler,
        },
        [
            <MultiItemSelection.Item id={clickedItemId} index={1} key="1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="2" index={2} key="2">Child 2</MultiItemSelection.Item>,
            <MultiItemSelection.Item id="3" index={3} key="3">Child 3</MultiItemSelection.Item>,
        ]
    );

    const editButtons = screen.getAllByLabelText('su-pen');
    await user.click(editButtons[0].closest('button'));
    expect(editHandler).toHaveBeenCalledWith(clickedItemId);
});

test('Clicking on an item should call its onClick handler', async() => {
    const user = userEvent.setup();
    const clickHandler = jest.fn();
    const {container} = renderMultiItemSelection(
        {
            label: 'I have handler',
            onItemClick: clickHandler,
            onItemEdit: jest.fn(),
        },
        [
            <MultiItemSelection.Item id={6} index={1} key="1" value="value1">Child 1</MultiItemSelection.Item>,
            <MultiItemSelection.Item id={3} index={2} key="2" value="value2">Child 2</MultiItemSelection.Item>,
        ]
    );

    const contentItems = container.querySelectorAll('.content');

    await user.click(contentItems[0]);
    expect(clickHandler).toHaveBeenLastCalledWith(6, 'value1');

    await user.click(contentItems[1]);
    expect(clickHandler).toHaveBeenLastCalledWith(3, 'value2');
});
