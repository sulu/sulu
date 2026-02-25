// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MultiItemSelection from '../MultiItemSelection';

test('Render an empty MultiItemSelection', () => {
    const {asFragment} = render(<MultiItemSelection label="I am empty" />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render an MultiItemSelection with children', () => {
    const {asFragment} = render(
        <MultiItemSelection label="I have children">
            <MultiItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
                onEdit={jest.fn()}
                onRemove={jest.fn()}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                disabled={true}
                id="3"
                index={3}
                onEdit={jest.fn()}
                onRemove={jest.fn()}
            >
                Child 3 (disabled)
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                allowRemoveWhileDisabled={true}
                disabled={true}
                id="4"
                index={4}
                onEdit={jest.fn()}
                onRemove={jest.fn()}
            >
                Child 4 (disabled with remove button)
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a disabled MultiItemSelection with children', () => {
    const {asFragment} = render(
        <MultiItemSelection disabled={true} label="I am disabled">
            <MultiItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a button on the right with options and a value', () => {
    const rightButton = {label: 'Test', onClick: jest.fn(), options: [{label: 'Test1', value: 'test-1'}]};

    const {asFragment} = render(
        <MultiItemSelection rightButton={rightButton}>
            <MultiItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a button on the right with options including icons and a value', () => {
    const rightButton = {
        label: 'Test',
        onClick: jest.fn(),
        options: [{icon: 'su-default', label: 'Test1', value: 'test-1'}],
    };

    const {asFragment} = render(
        <MultiItemSelection rightButton={rightButton}>
            <MultiItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a not sortable MultiItemSelection with children', () => {
    const {asFragment} = render(
        <MultiItemSelection label="I have children" sortable={false}>
            <MultiItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render an MultiItemSelection while loading', () => {
    const {asFragment} = render(<MultiItemSelection label="I am loading" loading={true} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Clicking the left and right button inside the header should call the right handler', async() => {
    const leftClickHandler = jest.fn();
    const rightClickHandler = jest.fn();
    const user = userEvent.setup();
    const leftButtonConfig = {
        icon: 'su-plus',
        onClick: leftClickHandler,
    };
    const rightButtonConfig = {
        icon: 'fa-gear',
        onClick: rightClickHandler,
    };
    const {asFragment} = render(
        <MultiItemSelection
            label="I have handler"
            leftButton={leftButtonConfig}
            rightButton={rightButtonConfig}
        >
            <MultiItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    expect(asFragment()).toMatchSnapshot();

    const leftButton = screen.getByRole('button', {name: 'su-plus'});
    const rightButton = screen.getByRole('button', {name: 'fa-gear'});

    await user.click((leftButton: any));
    expect(leftClickHandler).toBeCalled();

    await user.click((rightButton: any));
    expect(rightClickHandler).toBeCalled();
});

test('Clicking the left button inside the header should call the right handler after choosing an option', async() => {
    const leftClickHandler = jest.fn();
    const user = userEvent.setup();
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

    render(
        <MultiItemSelection
            label="I have handler"
            leftButton={leftButtonConfig}
        >
            <MultiItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    const leftButton = screen.getByRole('button');

    await user.click((leftButton: any));
    await user.click(screen.getByText('Test1'));
    expect(leftClickHandler).toBeCalledWith('test1');

    await user.click((leftButton: any));
    await user.click(screen.getByText('Test2'));
    expect(leftClickHandler).toBeCalledWith('test2');
});

test('Clicking on the remove button inside an item should call the remove handler on the parent component', async() => {
    const removeHandler = jest.fn();
    const user = userEvent.setup();
    const clickedItemId = 1;
    render(
        <MultiItemSelection
            label="I have handler"
            onItemRemove={removeHandler}
        >
            <MultiItemSelection.Item
                id={clickedItemId}
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    const removeIcon = screen.getAllByLabelText('su-trash-alt')[0];
    const removeButton = removeIcon.closest('button');
    expect(removeButton).toBeTruthy();

    await user.click((removeButton: any));
    expect(removeHandler).toHaveBeenCalledWith(clickedItemId);
});

test('Clicking on the edit button inside an item should call the edit handler on the parent component', async() => {
    const editHandler = jest.fn();
    const user = userEvent.setup();
    const clickedItemId = 1;
    render(
        <MultiItemSelection
            label="I have handler"
            onItemEdit={editHandler}
        >
            <MultiItemSelection.Item
                id={clickedItemId}
                index={1}
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    const editIcon = screen.getAllByLabelText('su-pen')[0];
    const editButton = editIcon.closest('button');
    expect(editButton).toBeTruthy();

    await user.click((editButton: any));
    expect(editHandler).toHaveBeenCalledWith(clickedItemId);
});

test('Clicking on an item should call its onClick handler', async() => {
    const clickHandler = jest.fn();
    const user = userEvent.setup();
    render(
        <MultiItemSelection
            label="I have handler"
            onItemClick={clickHandler}
            onItemEdit={jest.fn()}
        >
            <MultiItemSelection.Item
                id={6}
                index={1}
                value="value1"
            >
                Child 1
            </MultiItemSelection.Item>
            <MultiItemSelection.Item
                id={3}
                index={2}
                value="value2"
            >
                Child 2
            </MultiItemSelection.Item>
        </MultiItemSelection>
    );

    await user.click(screen.getByRole('button', {name: 'Child 1'}));
    expect(clickHandler).toHaveBeenLastCalledWith(6, 'value1');

    await user.click(screen.getByRole('button', {name: 'Child 2'}));
    expect(clickHandler).toHaveBeenLastCalledWith(3, 'value2');
});
