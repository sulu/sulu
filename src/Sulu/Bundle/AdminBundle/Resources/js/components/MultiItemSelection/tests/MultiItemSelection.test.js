// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import MultiItemSelection from '../MultiItemSelection';

test('Render an empty MultiItemSelection', () => {
    expect(render(<MultiItemSelection label="I am empty" />)).toMatchSnapshot();
});

test('Render an MultiItemSelection with children', () => {
    expect(render(
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
    )).toMatchSnapshot();
});

test('Render a disabled MultiItemSelection with children', () => {
    expect(render(
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
    )).toMatchSnapshot();
});

test('Render a button on the right with options and a value', () => {
    const rightButton = {label: 'Test', onClick: jest.fn(), options: [{label: 'Test1', value: 'test-1'}]};

    expect(render(
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
    )).toMatchSnapshot();
});

test('Render a button on the right with options including icons and a value', () => {
    const rightButton = {
        label: 'Test',
        onClick: jest.fn(),
        options: [{icon: 'su-default', label: 'Test1', value: 'test-1'}],
    };

    expect(render(
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
    )).toMatchSnapshot();
});

test('Render a not sortable MultiItemSelection with children', () => {
    expect(render(
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
    )).toMatchSnapshot();
});

test('Render an MultiItemSelection while loading', () => {
    expect(render(<MultiItemSelection label="I am loading" loading={true} />)).toMatchSnapshot();
});

test('Clicking the left and right button inside the header should call the right handler', () => {
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
    const multiItemSelection = mount(
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

    expect(multiItemSelection.render()).toMatchSnapshot();

    multiItemSelection.find('.button.left').simulate('click');
    expect(leftClickHandler).toBeCalled();

    multiItemSelection.find('.button.right').simulate('click');
    expect(rightClickHandler).toBeCalled();
});

test('Clicking the left button inside the header should call the right handler after choosing an option', () => {
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

    const multiItemSelection = mount(
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

    multiItemSelection.find('Button[icon="su-plus"]').simulate('click');
    multiItemSelection.find('ArrowMenu Action').at(0).simulate('click');
    expect(leftClickHandler).toBeCalledWith('test1');

    multiItemSelection.find('Button[icon="su-plus"]').simulate('click');
    multiItemSelection.find('ArrowMenu Action').at(1).simulate('click');
    expect(leftClickHandler).toBeCalledWith('test2');
});

test('Clicking on the remove button inside an item should call the remove handler on the parent component', () => {
    const removeHandler = jest.fn();
    const clickedItemId = 1;
    const multiItemSelection = mount(
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

    multiItemSelection.find('Icon[name="su-trash-alt"]').at(0).parent().prop('onClick')();
    expect(removeHandler).toHaveBeenCalledWith(clickedItemId);
});

test('Clicking on the edit button inside an item should call the edit handler on the parent component', () => {
    const editHandler = jest.fn();
    const clickedItemId = 1;
    const multiItemSelection = mount(
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

    multiItemSelection.find('Icon[name="su-pen"]').at(0).parent().prop('onClick')();
    expect(editHandler).toHaveBeenCalledWith(clickedItemId);
});
