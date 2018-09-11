//@flow
import {render, mount} from 'enzyme';
import React from 'react';
import Item from '../Item';

test('The component should render', () => {
    const handleClick = jest.fn();
    const item = render(
        <Item
            icon="su-search"
            onClick={handleClick}
            title="Test"
            value="test_1"
        />
    );
    expect(item).toMatchSnapshot();
});

test('The component should render active', () => {
    const handleClick = jest.fn();
    const item = render(
        <Item
            active={true}
            icon="su-search"
            onClick={handleClick}
            title="Test"
            value="test_1"
        />
    );
    expect(item).toMatchSnapshot();
});

test('The component should render with children', () => {
    const handleClick = jest.fn();
    const item = render(
        <Item
            icon="su-cog"
            title="Settings"
            value="settings"
        >
            <Item
                onClick={handleClick}
                title="Settings 1"
                value="settings_1"
            />
            <Item
                onClick={handleClick}
                title="Settings 2"
                value="settings_2"
            />
        </Item>
    );
    expect(item).toMatchSnapshot();
});

test('The component should render with children an active child and expanded', () => {
    const handleClick = jest.fn();
    const item = render(
        <Item
            expanded={true}
            icon="su-cog"
            title="Settings"
            value="settings"
        >
            <Item
                onClick={handleClick}
                title="Settings 1"
                value="settings_1"
            />
            <Item
                active={true}
                onClick={handleClick}
                title="Settings 2"
                value="settings_2"
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
            expanded={true}
            icon="su-cog"
            onClick={handleItemClick}
            title="Settings"
            value="settings"
        >
            <Item
                onClick={handleSubItemClick}
                title="Settings 1"
                value="settings_1"
            />
            <Item
                active={true}
                onClick={handleSubItemClick}
                title="Settings 2"
                value="settings_2"
            />
        </Item>
    );

    item.find('.title').at(0).simulate('click');
    expect(handleItemClick).toBeCalledWith('settings');

    item.find('.title').at(2).simulate('click');
    expect(handleSubItemClick).toBeCalledWith('settings_2');
});
