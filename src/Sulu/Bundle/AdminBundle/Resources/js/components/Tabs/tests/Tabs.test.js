/* eslint-disable flowtype/require-valid-file-annotation */
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Badge from '../../Badge/Badge';
import Tabs from '../Tabs.js';

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

Object.defineProperty(window, 'getComputedStyle', {
    value: () => ({
        paddingLeft: 20.0,
        paddingRight: 20.0,
    }),
});

test('Render a Tabs component with type root', () => {
    const changeSpy = jest.fn();
    const {container} = render(
        <Tabs onSelect={changeSpy} selectedIndex={null} type="root">
            <Tabs.Tab>Tab 1</Tabs.Tab>
            <Tabs.Tab>Tab 2</Tabs.Tab>
            <Tabs.Tab>Tab 3</Tabs.Tab>
        </Tabs>
    );

    expect(container).toMatchSnapshot();
});

test('Render a Tabs component with type nested', () => {
    const changeSpy = jest.fn();
    const {container} = render(
        <Tabs onSelect={changeSpy} selectedIndex={null} type="nested">
            <Tabs.Tab>Tab 1</Tabs.Tab>
            <Tabs.Tab>Tab 2</Tabs.Tab>
            <Tabs.Tab>Tab 3</Tabs.Tab>
        </Tabs>
    );

    expect(container).toMatchSnapshot();
});

test('Render a Tabs component with type inline', () => {
    const changeSpy = jest.fn();
    const {container} = render(
        <Tabs onSelect={changeSpy} selectedIndex={null} type="inline">
            <Tabs.Tab>Tab 1</Tabs.Tab>
            <Tabs.Tab>Tab 2</Tabs.Tab>
            <Tabs.Tab>Tab 3</Tabs.Tab>
        </Tabs>
    );

    expect(container).toMatchSnapshot();
});

test('Render a Tabs component with a selected tab and a badge', () => {
    const changeSpy = jest.fn();
    const selectedTabIndex = 0;
    const {container} = render(
        <Tabs onSelect={changeSpy} selectedIndex={selectedTabIndex} type="root">
            <Tabs.Tab>Tab 1</Tabs.Tab>
            <Tabs.Tab>Tab 2</Tabs.Tab>
            <Tabs.Tab badges={[<Badge key="badge1">1</Badge>, <Badge key="badge2">2</Badge>]}>Tab 3</Tabs.Tab>
        </Tabs>
    );

    expect(container).toMatchSnapshot();
});

test('Clicking on a Tab should call the onSelect handler', async() => {
    const changeSpy = jest.fn();
    const selectedTabIndex = 0;

    render(
        <Tabs onSelect={changeSpy} selectedIndex={null}>
            <Tabs.Tab>Tab 1</Tabs.Tab>
            <Tabs.Tab>Tab 2</Tabs.Tab>
            <Tabs.Tab>Tab 3</Tabs.Tab>
        </Tabs>
    );

    const tab1 = screen.queryByText('Tab 1');
    await userEvent.click(tab1);

    expect(changeSpy).toHaveBeenCalledWith(selectedTabIndex);
});

test('ResizeObserver.disconnect should be called before component unmount', () => {
    const changeSpy = jest.fn();
    const selectedTabIndex = 0;

    const {unmount} = render(
        <Tabs onSelect={changeSpy} selectedIndex={selectedTabIndex}>
            <Tabs.Tab>Tab 1</Tabs.Tab>
            <Tabs.Tab>Tab 2</Tabs.Tab>
            <Tabs.Tab>Tab 3</Tabs.Tab>
            <Tabs.Tab>Tab 4</Tabs.Tab>
            <Tabs.Tab>Tab 5</Tabs.Tab>
            <Tabs.Tab>Tab 6</Tabs.Tab>
            <Tabs.Tab>Tab 7</Tabs.Tab>
            <Tabs.Tab>Tab 8</Tabs.Tab>
            <Tabs.Tab>Tab 9</Tabs.Tab>
            <Tabs.Tab>Tab 10</Tabs.Tab>
        </Tabs>
    );
    unmount();

    expect(ResizeObserver.mock.instances[0].disconnect).toBeCalled();
});
