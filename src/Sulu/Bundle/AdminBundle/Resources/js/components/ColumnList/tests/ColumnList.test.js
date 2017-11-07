/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';
import ColumnList from '../ColumnList';
import Column from '../Column';
import Item from '../Item';
import Toolbar from '../Toolbar';

test('The ColumnList component should render', () => {
    const onItemClick = () => {};

    const buttonsConfig = [
        {
            icon: 'heart',
            onClick: () => {},
        },
        {
            icon: 'pencil',
            onClick: () => {},
        },
    ];

    const toolbarItems = [
        {
            icon: 'plus',
            type: 'button',
            onClick: () => {},
        },
        {
            icon: 'search',
            type: 'button',
            onClick: () => {},
        },
        {
            icon: 'gear',
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
    ];

    const columnList = mount(
        <ColumnList
            buttons={buttonsConfig}
            onItemClick={onItemClick}
            toolbarItems={toolbarItems}
        >
            <Column>
                <Item id="1" selected="true">Item 1</Item>
                <Item id="2" hasChildren="true">Item 1</Item>
                <Item id="3">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1</Item>
                <Item id="1-2" hasChildren="true">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1-1">Item 1</Item>
                <Item id="1-1-2">Item 1</Item>
            </Column>
        </ColumnList>
    );
    expect(columnList).toMatchSnapshot();
});

test('The ColumnList component should trigger the item callback', () => {
    const onItemClick = jest.fn();

    const buttonsConfig = [
        {
            icon: 'heart',
            onClick: () => {},
        },
        {
            icon: 'pencil',
            onClick: () => {},
        },
    ];

    const toolbarItems = [
        {
            icon: 'plus',
            type: 'button',
            onClick: () => {},
        },
        {
            icon: 'search',
            type: 'button',
            onClick: () => {},
        },
        {
            icon: 'gear',
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
    ];

    const columnList = mount(
        <ColumnList
            buttons={buttonsConfig}
            onItemClick={onItemClick}
            toolbarItems={toolbarItems}
        >
            <Column>
                <Item id="1" selected="true">Item 1</Item>
                <Item id="2" hasChildren="true">Item 1</Item>
                <Item id="3">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1</Item>
                <Item id="1-2" hasChildren="true">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1-1">Item 1</Item>
                <Item id="1-1-2">Item 1</Item>
            </Column>
        </ColumnList>
    );
    const columns = columnList.find(Column);
    expect(columns.length).toBe(3);

    expect(columnList.find(Toolbar).length).toBe(3);

    columns.first().find(Item).first().simulate('click');
    columns.first().find(Item).at(2).simulate('click');
    columns.at(1).find(Item).first().simulate('click');

    expect(onItemClick.mock.calls.length).toBe(3);
    expect(onItemClick.mock.calls[0][0]).toBe('1');
    expect(onItemClick.mock.calls[1][0]).toBe('3');
    expect(onItemClick.mock.calls[2][0]).toBe('1-1');
});

test('The ColumnList component should handle which toolbar is active on mouse enter event', () => {
    const onItemClick = jest.fn();

    const buttonsConfig = [
        {
            icon: 'heart',
            onClick: () => {},
        },
        {
            icon: 'pencil',
            onClick: () => {},
        },
    ];

    const toolbarItems = [
        {
            icon: 'plus',
            type: 'button',
            onClick: () => {},
        },
        {
            icon: 'search',
            type: 'button',
            onClick: () => {},
        },
        {
            icon: 'gear',
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
    ];

    const columnList = mount(
        <ColumnList
            buttons={buttonsConfig}
            onItemClick={onItemClick}
            toolbarItems={toolbarItems}
        >
            <Column>
                <Item id="1" selected="true">Item 1</Item>
                <Item id="2" hasChildren="true">Item 1</Item>
                <Item id="3">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1</Item>
                <Item id="1-2" hasChildren="true">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1-1">Item 1</Item>
                <Item id="1-1-2">Item 1</Item>
            </Column>
        </ColumnList>
    );
    const columns = columnList.find(Column);
    expect(columnList.find(Column).at(0).props().active).toBe(true);
    expect(columnList.find(Column).at(1).props().active).toBe(false);
    expect(columnList.find(Column).at(2).props().active).toBe(false);

    columns.at(1).simulate('mouseEnter');
    expect(columnList.find(Column).at(0).props().active).toBe(false);
    expect(columnList.find(Column).at(1).props().active).toBe(true);
    expect(columnList.find(Column).at(2).props().active).toBe(false);

    columns.at(2).simulate('mouseEnter');
    expect(columnList.find(Column).at(0).props().active).toBe(false);
    expect(columnList.find(Column).at(1).props().active).toBe(false);
    expect(columnList.find(Column).at(2).props().active).toBe(true);

    columns.at(0).simulate('mouseEnter');
    expect(columnList.find(Column).at(0).props().active).toBe(true);
    expect(columnList.find(Column).at(1).props().active).toBe(false);
    expect(columnList.find(Column).at(2).props().active).toBe(false);
});
