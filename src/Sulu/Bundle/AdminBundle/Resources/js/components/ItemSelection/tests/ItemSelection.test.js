/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, mount} from 'enzyme';
import ItemSelection from '../ItemSelection';

test('Render an empty ItemSelection', () => {
    expect(render(<ItemSelection label="I am empty" />)).toMatchSnapshot();
});

test('Render an ItemSelection with children', () => {
    expect(render(
        <ItemSelection label="I have children">
            <ItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </ItemSelection.Item>
            <ItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </ItemSelection.Item>
            <ItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </ItemSelection.Item>
        </ItemSelection>
    )).toMatchSnapshot();
});

test('Clicking the left and right button inside the header should call the right handler', () => {
    const leftClickHandler = jest.fn();
    const rightClickHandler = jest.fn();
    const leftButtonConfig = {
        icon: 'plus',
        onClick: leftClickHandler,
    };
    const rightButtonConfig = {
        icon: 'gear',
        onClick: rightClickHandler,
    };
    const itemSelection = mount(
        <ItemSelection
            label="I have handler"
            leftButton={leftButtonConfig}
            rightButton={rightButtonConfig}
        >
            <ItemSelection.Item
                id="1"
                index={1}
            >
                Child 1
            </ItemSelection.Item>
            <ItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </ItemSelection.Item>
            <ItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </ItemSelection.Item>
        </ItemSelection>
    );

    expect(itemSelection.render()).toMatchSnapshot();

    itemSelection.find('.button.left').simulate('click');
    expect(leftClickHandler).toBeCalled();

    itemSelection.find('.button.right').simulate('click');
    expect(rightClickHandler).toBeCalled();
});

test('Clicking on the remove button inside an item should call the remove handler on the parent component', () => {
    const removeHandler = jest.fn();
    const clickedItemId = 1;
    const itemSelection = mount(
        <ItemSelection
            label="I have handler"
            onItemRemove={removeHandler}
        >
            <ItemSelection.Item
                id={clickedItemId}
                index={1}
            >
                Child 1
            </ItemSelection.Item>
            <ItemSelection.Item
                id="2"
                index={2}
            >
                Child 2
            </ItemSelection.Item>
            <ItemSelection.Item
                id="3"
                index={3}
            >
                Child 3
            </ItemSelection.Item>
        </ItemSelection>
    );

    itemSelection.find('.removeButton').at(0).simulate('click');
    expect(removeHandler).toHaveBeenCalledWith(clickedItemId);
});
