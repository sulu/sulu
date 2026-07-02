/* eslint-disable flowtype/require-valid-file-annotation */
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Badge from '../../Badge/Badge';
import Tabs from '../Tabs.js';

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function(callback) {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
    this.callback = callback;
});

Object.defineProperty(window, 'getComputedStyle', {
    value: () => ({
        paddingLeft: 20.0,
        paddingRight: 20.0,
        getPropertyValue: () => '',
    }),
});

function applyTabMeasurements(container) {
    const tabsRef = container.querySelector('.tabs');
    const tabsContainerWrapperRef = container.querySelector('.tabsContainerWrapper');
    const tabsContainerRef = container.querySelector('.tabsContainer');
    const tabRefs = container.querySelectorAll('.tab');

    if (!tabsRef || !tabsContainerWrapperRef || !tabsContainerRef) {
        throw new Error('Expected tabs container refs');
    }

    Object.defineProperty(tabsRef, 'offsetWidth', {value: 129, configurable: true});
    Object.defineProperty(tabsContainerWrapperRef, 'offsetWidth', {value: 55, configurable: true});
    Object.defineProperty(tabsContainerRef, 'offsetWidth', {value: 100, configurable: true});
    tabRefs.forEach((tabRef) => {
        Object.defineProperty(tabRef, 'offsetWidth', {value: 10, configurable: true});
    });

    ResizeObserver.mock.instances[0].callback();
}

function getCollapsedTabList() {
    const collapsedTabList = document.querySelector('.collapsedTabList');
    if (!collapsedTabList) {
        throw new Error('Expected collapsed tab list');
    }

    return collapsedTabList;
}

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

    expect(ResizeObserver.mock.instances[0].disconnect).toHaveBeenCalled();
});

test('Clicking on several non- and collapsed tabs', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const {container, rerender} = render(
        <Tabs onSelect={changeSpy} selectedIndex={0}>
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
    applyTabMeasurements(container);
    expect(screen.getByRole('button', {name: 'Tab 6'}).parentElement).toHaveClass('hidden');

    await user.click(screen.getByRole('button', {name: 'Tab 5'}));
    expect(changeSpy).toHaveBeenCalledWith(4);

    rerender(
        <Tabs onSelect={changeSpy} selectedIndex={4}>
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
    applyTabMeasurements(container);
    expect(screen.getByRole('button', {name: 'Tab 6'}).parentElement).toHaveClass('hidden');

    await user.click(screen.getByRole('button', {name: 'su-more-horizontal'}));
    await user.click(within(getCollapsedTabList()).getByRole('button', {name: 'Tab 7'}));
    expect(changeSpy).toHaveBeenCalledWith(6);

    rerender(
        <Tabs onSelect={changeSpy} selectedIndex={6}>
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
    applyTabMeasurements(container);
    expect(screen.getByRole('button', {name: 'Tab 5'}).parentElement).toHaveClass('hidden');
    expect(screen.getByRole('button', {name: 'Tab 7'}).parentElement).not.toHaveClass('hidden');

    await user.click(screen.getByRole('button', {name: 'su-more-horizontal'}));
    await user.click(within(getCollapsedTabList()).getByRole('button', {name: 'Tab 9'}));
    expect(changeSpy).toHaveBeenCalledWith(8);

    rerender(
        <Tabs onSelect={changeSpy} selectedIndex={8}>
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
    applyTabMeasurements(container);
    expect(screen.getByRole('button', {name: 'Tab 5'}).parentElement).toHaveClass('hidden');
    expect(screen.getByRole('button', {name: 'Tab 9'}).parentElement).not.toHaveClass('hidden');

    await user.click(screen.getByRole('button', {name: 'Tab 3'}));
    expect(changeSpy).toHaveBeenCalledWith(2);

    rerender(
        <Tabs onSelect={changeSpy} selectedIndex={2}>
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
    applyTabMeasurements(container);
    expect(screen.getByRole('button', {name: 'Tab 5'}).parentElement).toHaveClass('hidden');

    await user.click(screen.getByRole('button', {name: 'Tab 4'}));
    expect(changeSpy).toHaveBeenCalledWith(3);
});
