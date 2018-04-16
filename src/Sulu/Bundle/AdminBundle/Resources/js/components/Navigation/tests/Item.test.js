//@flow
import {render, mount} from 'enzyme';
import React from 'react';
import Item from '../Item';

test('The component should render', () => {
    const handleClick = jest.fn();
    const item = render(
        <Item
            icon="su-search"
            value="test_1"
            onClick={handleClick}
            title="Test"
        />
    );
    expect(item).toMatchSnapshot();
});

test('The component should render active', () => {
    const handleClick = jest.fn();
    const item = render(
        <Item
            icon="su-search"
            value="test_1"
            onClick={handleClick}
            title="Test"
            active={true}
        />
    );
    expect(item).toMatchSnapshot();
});

test('The component should render with children', () => {
    const handleClick = jest.fn();
    const item = render(
        <Item
            icon="su-cog"
            value="settings"
            title="Settings"
        >
            <Item
                value="settings_1"
                onClick={handleClick}
                title="Settings 1"
            />
            <Item
                value="settings_2"
                onClick={handleClick}
                title="Settings 2"
            />
        </Item>
    );
    expect(item).toMatchSnapshot();
});

test('The component should render with children an active child and expanded', () => {
    const handleClick = jest.fn();
    const item = render(
        <Item
            icon="su-cog"
            value="settings"
            title="Settings"
            expanded={true}
        >
            <Item
                value="settings_1"
                onClick={handleClick}
                title="Settings 1"
            />
            <Item
                value="settings_2"
                onClick={handleClick}
                title="Settings 2"
                active={true}
            />
        </Item>
    );
    expect(item).toMatchSnapshot();
});

test('The component should handle clicks correctly', () => {
    const handleItemClick = jest.fn();
    const handleSubItemClick = jest.fn();

    const item = mount(
        <Item
            icon="su-cog"
            value="settings"
            title="Settings"
            onClick={handleItemClick}
            expanded={true}
        >
            <Item
                value="settings_1"
                onClick={handleSubItemClick}
                title="Settings 1"
            />
            <Item
                value="settings_2"
                onClick={handleSubItemClick}
                title="Settings 2"
                active={true}
            />
        </Item>
    );

    item.find('.title').at(0).simulate('click');
    expect(handleItemClick).toBeCalledWith('settings');

    item.find('.title').at(2).simulate('click');
    expect(handleSubItemClick).toBeCalledWith('settings_2');
});
