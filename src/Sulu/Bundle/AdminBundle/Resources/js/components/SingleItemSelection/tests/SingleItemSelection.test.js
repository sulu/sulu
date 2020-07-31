// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import SingleItemSelection from '../SingleItemSelection';

test('Render with given children prop', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(<SingleItemSelection leftButton={leftButton}>Test Item</SingleItemSelection>)).toMatchSnapshot();
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

    expect(render(
        <SingleItemSelection leftButton={leftButton} rightButton={rightButton}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
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

    expect(render(
        <SingleItemSelection leftButton={leftButton} rightButton={rightButton}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render with custom className', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection className="test" leftButton={leftButton}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render in disabled state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection
            disabled={true}
            leftButton={leftButton}
            onRemove={jest.fn()}
        >
            Test Item
        </SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render in item-disabled state without remove button', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection
            allowRemoveWhileItemDisabled={false}
            itemDisabled={true}
            leftButton={leftButton}
            onRemove={jest.fn()}
        >
            Test Item
        </SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render in item-disabled state with remove button', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection
            allowRemoveWhileItemDisabled={true}
            itemDisabled={true}
            leftButton={leftButton}
            onRemove={jest.fn()}
        >
            Test Item
        </SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render in loading state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection leftButton={leftButton} loading={true}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render in loading state with no children', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection leftButton={leftButton} loading={true} />
    )).toMatchSnapshot();
});

test('Render in invalid state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection leftButton={leftButton} valid={false}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render with given onRemove prop', () => {
    const leftButton = {
        icon: 'su-page',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection leftButton={leftButton} onRemove={jest.fn()}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render with emptyText if no children have been passed', () => {
    const leftButton = {
        icon: 'su-page',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection emptyText="Nothing!" leftButton={leftButton} onRemove={jest.fn()} />
    )).toMatchSnapshot();
});

test('Call onClick callback if left button is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const singleItemSelection = shallow(<SingleItemSelection leftButton={leftButton} />);

    singleItemSelection.find('Button').prop('onClick')();

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

    const singleItemSelection = mount(<SingleItemSelection leftButton={leftButton} rightButton={rightButton} />);

    singleItemSelection.find('Button[icon="su-display-default"]').simulate('click');
    singleItemSelection.find('Action[value="test1"]').simulate('click');

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

    const singleItemSelection = shallow(<SingleItemSelection leftButton={leftButton} rightButton={rightButton} />);

    singleItemSelection.find('Button[icon="su-display-default"]').prop('onClick')();

    expect(rightButton.onClick).toBeCalledWith();
});

test('Call onItemClick callback should not be called if item is clicked but no id is given', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const itemClickSpy = jest.fn();
    const singleItemSelection = shallow(<SingleItemSelection leftButton={leftButton} onItemClick={itemClickSpy} />);

    singleItemSelection.find('.item').prop('onClick')();

    expect(itemClickSpy).not.toBeCalled();
});

test('Call onItemClick callback should not be called if item is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const value = {id: 5};

    const itemClickSpy = jest.fn();
    const singleItemSelection = shallow(
        <SingleItemSelection id={5} leftButton={leftButton} onItemClick={itemClickSpy} value={value} />
    );

    singleItemSelection.find('.item').prop('onClick')();

    expect(itemClickSpy).toBeCalledWith(5, value);
});

test('Call onRemove callback if remove button is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const removeSpy = jest.fn();
    const singleItemSelection = shallow(<SingleItemSelection leftButton={leftButton} onRemove={removeSpy} />);

    singleItemSelection.find('.removeButton').prop('onClick')();

    expect(removeSpy).toBeCalledWith();
});
