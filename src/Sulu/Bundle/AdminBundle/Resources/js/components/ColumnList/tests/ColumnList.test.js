// @flow
import React from 'react';
import {fireEvent, render} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ColumnList from '../ColumnList';
import Column from '../Column';
import Item from '../Item';
import getMockCallArg from '../../../utils/TestHelper/getMockCallArg';

jest.mock('../columnList.scss', () => new Proxy({}, {
    get(target, key) {
        if (key === '__esModule') {
            return false;
        }
        if (key === 'columnWidth') {
            return '270px';
        }

        return key;
    },
}));

function createDefaultChildren(buttonsConfig?: Array<Object> = []) {
    return [
        <Column key="column-1">
            <Item buttons={buttonsConfig} id="1" selected={true}>Item 1</Item>
            <Item buttons={buttonsConfig} hasChildren={true} id="2">Item 1</Item>
            <Item id="3">Item 1</Item>
        </Column>,
        <Column key="column-2">
            <Item buttons={buttonsConfig} id="1-1">Item 1</Item>
            <Item buttons={buttonsConfig} hasChildren={true} id="1-2">Item 1</Item>
        </Column>,
        <Column key="column-3">
            <Item buttons={buttonsConfig} id="1-1-1">Item 1</Item>
            <Item buttons={buttonsConfig} id="1-1-2">Item 1</Item>
        </Column>,
    ];
}

function setElementSize(element: HTMLElement, width: number, scrollWidth?: number) {
    Object.defineProperty(element, 'clientWidth', {configurable: true, value: width});
    if (scrollWidth !== undefined) {
        Object.defineProperty(element, 'scrollWidth', {configurable: true, value: scrollWidth});
    }
}

test('The ColumnList component should render in a non-scrolling container', () => {
    const {asFragment} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => undefined)}>
            {createDefaultChildren()}
            <Column loading={true} />
        </ColumnList>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('The ColumnList component should render with toolbar and buttons', () => {
    const buttonsConfig = [
        {
            icon: 'fa-heart',
            onClick: () => {},
        },
        {
            icon: 'fa-pencil',
            onClick: () => {},
            visible: false,
        },
    ];

    const toolbarItemsProvider = jest.fn(() => [
        {
            index: 0,
            icon: 'fa-plus',
            type: 'button',
            onClick: () => {},
        },
        {
            index: 0,
            icon: 'fa-search',
            type: 'button',
            onClick: () => {},
        },
        {
            index: 0,
            icon: 'fa-gear',
            type: 'dropdown',
            options: [
                {label: 'Option1 ', onClick: () => {}},
                {label: 'Option2 ', onClick: () => {}},
            ],
        },
    ]);

    const {asFragment} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={toolbarItemsProvider}>
            {createDefaultChildren(buttonsConfig)}
            <Column loading={true} />
        </ColumnList>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('The ColumnList component should render in a scrolling container', async() => {
    const user = userEvent.setup();
    const toolbarItemsProvider = jest.fn(() => [
        {
            index: 0,
            icon: 'fa-plus',
            type: 'button',
            onClick: () => {},
        },
    ]);

    const {asFragment} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={toolbarItemsProvider}>
            {createDefaultChildren()}
            <Column loading={true} />
        </ColumnList>
    );

    const columnListContainer = document.querySelector('.columnListContainer');
    if (!columnListContainer) {
        throw new Error('Expected column list container');
    }

    setElementSize(columnListContainer, 500, 600);
    columnListContainer.scrollLeft = 20;
    fireEvent.scroll(columnListContainer);
    await user.hover(document.querySelectorAll('.column')[2]);

    expect(asFragment()).toMatchSnapshot();
});

test('The ColumnList component should trigger the given onItemClick callback', async() => {
    const user = userEvent.setup();
    const onItemClick = jest.fn();

    render(
        <ColumnList onItemClick={onItemClick} toolbarItemsProvider={jest.fn(() => [])}>
            {createDefaultChildren()}
        </ColumnList>
    );
    const items = document.querySelectorAll('.item');
    expect(items).toHaveLength(7);

    await user.click(items[0]);
    await user.click(items[2]);
    await user.click(items[3]);

    expect(onItemClick.mock.calls.length).toBe(3);
    expect(getMockCallArg(onItemClick, 0, 0)).toBe('1');
    expect(getMockCallArg(onItemClick, 1, 0)).toBe('3');
    expect(getMockCallArg(onItemClick, 2, 0)).toBe('1-1');
});

test('The ColumnList component should trigger the given onItemDoubleClick callback', async() => {
    const user = userEvent.setup();
    const onItemDoubleClickSpy = jest.fn();

    render(
        <ColumnList
            onItemClick={jest.fn()}
            onItemDoubleClick={onItemDoubleClickSpy}
            toolbarItemsProvider={jest.fn(() => [])}
        >
            {createDefaultChildren()}
        </ColumnList>
    );
    const items = document.querySelectorAll('.item');
    expect(items).toHaveLength(7);

    await user.dblClick(items[0]);
    await user.dblClick(items[2]);
    await user.dblClick(items[3]);

    expect(onItemDoubleClickSpy.mock.calls.length).toBe(3);
    expect(getMockCallArg(onItemDoubleClickSpy, 0, 0)).toBe('1');
    expect(getMockCallArg(onItemDoubleClickSpy, 1, 0)).toBe('3');
    expect(getMockCallArg(onItemDoubleClickSpy, 2, 0)).toBe('1-1');
});

test('The ColumnList component should handle which toolbar is active on mouse enter event', async() => {
    const user = userEvent.setup();
    const buttonClickSpy = jest.fn();

    const toolbarItemsProvider = jest.fn(() => [
        {
            icon: 'fa-plus',
            type: 'button',
            onClick: buttonClickSpy,
        },
    ]);

    render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={toolbarItemsProvider}>
            {createDefaultChildren()}
        </ColumnList>
    );

    expect(toolbarItemsProvider).toHaveBeenLastCalledWith(0);
    await user.click(document.querySelector('.toolbarContainer button'));
    expect(buttonClickSpy).toHaveBeenCalledTimes(1);

    const columns = document.querySelectorAll('.column');
    await user.hover(columns[1]);
    expect(toolbarItemsProvider).toHaveBeenLastCalledWith(1);
    await user.click(document.querySelector('.toolbarContainer button'));
    expect(buttonClickSpy).toHaveBeenCalledTimes(2);

    await user.hover(columns[2]);
    expect(toolbarItemsProvider).toHaveBeenLastCalledWith(2);
    await user.click(document.querySelector('.toolbarContainer button'));
    expect(buttonClickSpy).toHaveBeenCalledTimes(3);
});

test('Should move the toolbar container to the correct position', async() => {
    const user = userEvent.setup();
    const toolbarItemsProvider = jest.fn(() => [
        {
            icon: 'fa-plus',
            type: 'button',
            onClick: jest.fn(),
        },
    ]);

    render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={toolbarItemsProvider}>
            <Column />
            <Column />
            <Column />
        </ColumnList>
    );

    const toolbarContainer = document.querySelector('.toolbarContainer');
    if (!toolbarContainer) {
        throw new Error('Expected toolbar container');
    }
    expect(toolbarContainer).toHaveStyle({marginLeft: '0px'});

    const columnListContainer = document.querySelector('.columnListContainer');
    if (!columnListContainer) {
        throw new Error('Expected column list container');
    }

    columnListContainer.scrollLeft = 35;
    fireEvent.scroll(columnListContainer);
    await user.hover(document.querySelectorAll('.column')[2]);

    expect(toolbarContainer).toHaveStyle({marginLeft: '505px'});
});

test('Should set classes if toolbar is active on first or last visible column', async() => {
    const user = userEvent.setup();
    render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            <Column />
            <Column />
            <Column />
        </ColumnList>
    );

    const columnListContainer = document.querySelector('.columnListContainer');
    if (!columnListContainer) {
        throw new Error('Expected column list container');
    }

    expect(columnListContainer.className).toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnListContainer.className).toEqual(expect.stringContaining('lastVisibleColumnActive'));

    setElementSize(columnListContainer, 500);
    columnListContainer.scrollLeft = 20;
    fireEvent.scroll(columnListContainer);
    await user.hover(document.querySelectorAll('.column')[0]);

    expect(columnListContainer.className).toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnListContainer.className).not.toEqual(expect.stringContaining('lastVisibleColumnActive'));

    await user.hover(document.querySelectorAll('.column')[2]);

    expect(columnListContainer.className).not.toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnListContainer.className).toEqual(expect.stringContaining('lastVisibleColumnActive'));
});

test('Should scroll to the last column when new column is loaded', () => {
    const {rerender} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            <Column />
        </ColumnList>
    );

    const columnListContainer = document.querySelector('.columnListContainer');
    if (!columnListContainer) {
        throw new Error('Expected column list container');
    }

    setElementSize(columnListContainer, 500);
    columnListContainer.scrollLeft = 0;

    rerender(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            <Column key={1} />
            <Column key={2} />
            <Column key={3} />
        </ColumnList>
    );

    expect(columnListContainer.scrollLeft).toEqual(540);
});

test('Should not scroll to the last column when other props are updated', () => {
    const children = [<Column key={1} />];
    const {rerender} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            {children}
        </ColumnList>
    );

    const columnListContainer = document.querySelector('.columnListContainer');
    if (!columnListContainer) {
        throw new Error('Expected column list container');
    }
    setElementSize(columnListContainer, 500);
    columnListContainer.scrollLeft = 10;

    rerender(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            {children}
        </ColumnList>
    );

    expect(columnListContainer.scrollLeft).toEqual(10);
});
