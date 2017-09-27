/* eslint-disable flowtype/require-valid-file-annotation */
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

    multiItemSelection.find('.removeButton').at(0).simulate('click');
    expect(removeHandler).toHaveBeenCalledWith(clickedItemId);
});
