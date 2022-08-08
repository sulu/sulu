//@flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Item from '../Item';

test('The component should render', () => {
    const handleClick = jest.fn();
    const {container} = render(
        <Item
            icon="su-search"
            onClick={handleClick}
            title="Test"
            value="test_1"
        />
    );
    expect(container).toMatchSnapshot();
});

test('The component should render active', () => {
    const handleClick = jest.fn();
    const {container} = render(
        <Item
            active={true}
            icon="su-search"
            onClick={handleClick}
            title="Test"
            value="test_1"
        />
    );
    expect(container).toMatchSnapshot();
});

test('The component should render with children', () => {
    const handleClick = jest.fn();
    const {container} = render(
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
    expect(container).toMatchSnapshot();
});

test('The component should render with children an active child and expanded', () => {
    const handleClick = jest.fn();
    const {container} = render(
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
    expect(container).toMatchSnapshot();
});

test('The component should handle clicks correctly', async() => {
    const handleItemClick = jest.fn();
    const handleSubItemClick = jest.fn();

    render(
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

    await userEvent.click(screen.queryByText('Settings'));
    expect(handleItemClick).toBeCalledWith('settings');

    await userEvent.click(screen.queryByText(/Settings 2/));
    expect(handleSubItemClick).toBeCalledWith('settings_2');
});
