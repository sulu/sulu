/* eslint-disable testing-library/no-container, testing-library/prefer-user-event, jest-dom/prefer-to-have-style */
// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import ColumnList from '../ColumnList';
import Column from '../Column';
import Item from '../Item';

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

function createColumns(withLoading = true) {
    const columns = [
        <Column key="column-1">
            <Item id="1" selected={true}>Item 1</Item>
            <Item hasChildren={true} id="2">Item 2</Item>
            <Item id="3">Item 3</Item>
        </Column>,
        <Column key="column-2">
            <Item id="1-1">Item 1-1</Item>
            <Item hasChildren={true} id="1-2">Item 1-2</Item>
        </Column>,
        <Column key="column-3">
            <Item id="1-1-1">Item 1-1-1</Item>
            <Item id="1-1-2">Item 1-1-2</Item>
        </Column>,
    ];

    if (withLoading) {
        columns.push(<Column key="column-loading" loading={true} />);
    }

    return columns;
}

function getRequiredElement(container, selector) {
    const element = container.querySelector(selector);

    if (!element) {
        throw new Error(`Expected element for selector "${selector}"`);
    }

    return element;
}

test('The ColumnList component should render in a non-scrolling container', () => {
    const onItemClick = jest.fn();
    const toolbarItemsProvider = jest.fn(() => undefined);
    const {asFragment} = render(
        <ColumnList
            onItemClick={onItemClick}
            toolbarItemsProvider={toolbarItemsProvider}
        >
            {createColumns()}
        </ColumnList>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('The ColumnList component should render without ', () => {
    const onItemClick = jest.fn();
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
                {
                    label: 'Option1 ',
                    onClick: () => {},
                },
                {
                    label: 'Option2 ',
                    onClick: () => {},
                },
            ],
        },
    ]);

    const {asFragment} = render(
        <ColumnList
            onItemClick={onItemClick}
            toolbarItemsProvider={toolbarItemsProvider}
        >
            <Column>
                <Item buttons={buttonsConfig} id="1" selected={true}>Item 1</Item>
                <Item buttons={buttonsConfig} hasChildren={true} id="2">Item 2</Item>
                <Item id="3">Item 3</Item>
            </Column>
            <Column>
                <Item buttons={buttonsConfig} id="1-1">Item 1-1</Item>
                <Item buttons={buttonsConfig} hasChildren={true} id="1-2">Item 1-2</Item>
            </Column>
            <Column>
                <Item buttons={buttonsConfig} id="1-1-1">Item 1-1-1</Item>
                <Item buttons={buttonsConfig} id="1-1-2">Item 1-1-2</Item>
            </Column>
            <Column loading={true} />
        </ColumnList>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('The ColumnList component should render in a scrolling container', () => {
    const onItemClick = jest.fn();
    const toolbarItemsProvider = jest.fn(() => [
        {
            index: 0,
            icon: 'fa-plus',
            type: 'button',
            onClick: () => {},
        },
    ]);
    const {asFragment, container} = render(
        <ColumnList
            onItemClick={onItemClick}
            toolbarItemsProvider={toolbarItemsProvider}
        >
            {createColumns()}
        </ColumnList>
    );

    const columnListContainer = getRequiredElement(container, '.columnListContainer');
    Object.defineProperty(columnListContainer, 'clientWidth', {configurable: true, value: 500});
    Object.defineProperty(columnListContainer, 'scrollWidth', {configurable: true, value: 600});
    Object.defineProperty(columnListContainer, 'scrollLeft', {configurable: true, value: 20, writable: true});

    const columns = container.querySelectorAll('.column');
    fireEvent.mouseEnter(columns[2]);
    fireEvent.scroll(columnListContainer);

    expect(asFragment()).toMatchSnapshot();
});

test('The ColumnList component should trigger the given onItemClick callback', async() => {
    const user = userEvent.setup();
    const onItemClick = jest.fn();

    render(
        <ColumnList
            onItemClick={onItemClick}
            toolbarItemsProvider={jest.fn(() => [])}
        >
            <Column>
                <Item id="1" selected={true}>Item 1</Item>
                <Item hasChildren={true} id="2">Item 2</Item>
                <Item id="3">Item 3</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1-1</Item>
                <Item hasChildren={true} id="1-2">Item 1-2</Item>
            </Column>
        </ColumnList>
    );

    await user.click(screen.getByRole('button', {name: 'Item 1'}));
    await user.click(screen.getByRole('button', {name: 'Item 3'}));
    await user.click(screen.getByRole('button', {name: 'Item 1-1'}));

    expect(onItemClick).toHaveBeenCalledTimes(3);
    expect(onItemClick).toHaveBeenNthCalledWith(1, '1');
    expect(onItemClick).toHaveBeenNthCalledWith(2, '3');
    expect(onItemClick).toHaveBeenNthCalledWith(3, '1-1');
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
            <Column>
                <Item id="1" selected={true}>Item 1</Item>
                <Item hasChildren={true} id="2">Item 2</Item>
                <Item id="3">Item 3</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1-1</Item>
                <Item hasChildren={true} id="1-2">Item 1-2</Item>
            </Column>
        </ColumnList>
    );

    await user.dblClick(screen.getByRole('button', {name: 'Item 1'}));
    await user.dblClick(screen.getByRole('button', {name: 'Item 3'}));
    await user.dblClick(screen.getByRole('button', {name: 'Item 1-1'}));

    expect(onItemDoubleClickSpy).toHaveBeenCalledTimes(3);
    expect(onItemDoubleClickSpy).toHaveBeenNthCalledWith(1, '1');
    expect(onItemDoubleClickSpy).toHaveBeenNthCalledWith(2, '3');
    expect(onItemDoubleClickSpy).toHaveBeenNthCalledWith(3, '1-1');
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

    const {container} = render(
        <ColumnList
            onItemClick={jest.fn()}
            toolbarItemsProvider={toolbarItemsProvider}
        >
            <Column>
                <Item id="1" selected={true}>Item 1</Item>
                <Item hasChildren={true} id="2">Item 2</Item>
                <Item id="3">Item 3</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1</Item>
                <Item hasChildren={true} id="1-2">Item 1-2</Item>
            </Column>
            <Column>
                <Item id="1-1-1">Item 1-1-1</Item>
                <Item id="1-1-2">Item 1-1-2</Item>
            </Column>
        </ColumnList>
    );
    const columns = container.querySelectorAll('.column');
    expect(toolbarItemsProvider).toHaveBeenLastCalledWith(0);
    await user.click(screen.getByLabelText('fa-plus').closest('button'));
    expect(buttonClickSpy).toHaveBeenCalledWith();

    fireEvent.mouseEnter(columns[1]);
    expect(toolbarItemsProvider).toHaveBeenLastCalledWith(1);
    await user.click(screen.getByLabelText('fa-plus').closest('button'));
    expect(buttonClickSpy).toHaveBeenLastCalledWith();

    fireEvent.mouseEnter(columns[2]);
    expect(toolbarItemsProvider).toHaveBeenLastCalledWith(2);
    await user.click(screen.getByLabelText('fa-plus').closest('button'));
    expect(buttonClickSpy).toHaveBeenLastCalledWith();
});

test('Should move the toolbar container to the beginning if active column does not exist anymore', () => {
    const toolbarItemsProvider = jest.fn(() => [
        {
            icon: 'fa-plus',
            type: 'button',
            onClick: jest.fn(),
        },
    ]);

    const {container, rerender} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={toolbarItemsProvider}>
            <Column />
            <Column />
            <Column />
        </ColumnList>
    );

    const columns = container.querySelectorAll('.column');
    fireEvent.mouseEnter(columns[2]);

    rerender(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={toolbarItemsProvider}>
            <Column />
            <Column />
        </ColumnList>
    );

    expect(getRequiredElement(container, '.toolbarContainer').style.marginLeft).toEqual('0px');
});

test('Should move the toolbar container to the correct position', () => {
    const toolbarItemsProvider = jest.fn(() => [
        {
            icon: 'fa-plus',
            type: 'button',
            onClick: jest.fn(),
        },
    ]);

    const {container} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={toolbarItemsProvider}>
            <Column />
            <Column />
            <Column />
        </ColumnList>
    );
    const toolbarContainer = getRequiredElement(container, '.toolbarContainer');
    const columnListContainer = getRequiredElement(container, '.columnListContainer');

    expect(toolbarContainer.style.marginLeft).toEqual('0px');

    Object.defineProperty(columnListContainer, 'scrollLeft', {configurable: true, value: 35, writable: true});
    const columns = container.querySelectorAll('.column');
    fireEvent.mouseEnter(columns[2]);
    fireEvent.scroll(columnListContainer);

    expect(toolbarContainer.style.marginLeft).toEqual('505px');
});

test('Should set classes if the toolbar is active on the first or last visible column', () => {
    const {container} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            <Column />
            <Column />
            <Column />
        </ColumnList>
    );
    const columnListContainer = getRequiredElement(container, '.columnListContainer');

    expect(columnListContainer.className).toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnListContainer.className).toEqual(expect.stringContaining('lastVisibleColumnActive'));

    Object.defineProperty(columnListContainer, 'clientWidth', {configurable: true, value: 500});
    Object.defineProperty(columnListContainer, 'scrollLeft', {configurable: true, value: 20, writable: true});
    fireEvent.mouseEnter(container.querySelectorAll('.column')[0]);
    fireEvent.scroll(columnListContainer);

    expect(columnListContainer.className).toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnListContainer.className).not.toEqual(expect.stringContaining('lastVisibleColumnActive'));

    fireEvent.mouseEnter(container.querySelectorAll('.column')[2]);
    fireEvent.scroll(columnListContainer);

    expect(columnListContainer.className).not.toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnListContainer.className).toEqual(expect.stringContaining('lastVisibleColumnActive'));
});

test('Should scroll to the last column when new column is loaded', () => {
    const {container, rerender} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            <Column />
        </ColumnList>
    );
    const columnListContainer = getRequiredElement(container, '.columnListContainer');

    Object.defineProperty(columnListContainer, 'clientWidth', {configurable: true, value: 500});
    Object.defineProperty(columnListContainer, 'scrollLeft', {configurable: true, value: 0, writable: true});

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
    const children = [
        <Column key={1} />,
    ];
    const {container, rerender} = render(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            {children}
        </ColumnList>
    );
    const columnListContainer = getRequiredElement(container, '.columnListContainer');

    Object.defineProperty(columnListContainer, 'clientWidth', {configurable: true, value: 500});
    Object.defineProperty(columnListContainer, 'scrollLeft', {configurable: true, value: 10, writable: true});

    rerender(
        <ColumnList onItemClick={jest.fn()} toolbarItemsProvider={jest.fn(() => [])}>
            {children}
        </ColumnList>
    );

    expect(columnListContainer.scrollLeft).toEqual(10);
});
