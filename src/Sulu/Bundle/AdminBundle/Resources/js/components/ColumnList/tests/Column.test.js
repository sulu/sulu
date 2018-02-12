// @flow
import React from 'react';
import {mount} from 'enzyme';
import Column from '../Column';

test('The Column component should render', () => {
    const buttonsConfig = [
        {
            icon: 'fa-heart',
            onClick: () => {},
        },
        {
            icon: 'fa-pencil',
            onClick: () => {},
        },
    ];

    const toolbarItems = [
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
    ];

    const column = mount(
        <Column active={true} toolbarItems={toolbarItems} index={0} buttons={buttonsConfig} />
    );
    expect(column.render()).toMatchSnapshot();

    const column2 = mount(
        <Column active={false} toolbarItems={toolbarItems} index={0} buttons={buttonsConfig} />
    );
    expect(column2.render()).toMatchSnapshot();
});
