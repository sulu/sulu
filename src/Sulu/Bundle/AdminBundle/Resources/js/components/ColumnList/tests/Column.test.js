/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';
import Column from '../Column';

test('The Column component should render', () => {
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

    const column = mount(
        <Column active={true} toolbarItems={toolbarItems} index={0} buttons={buttonsConfig} />
    );
    expect(column.render()).toMatchSnapshot();

    const column2 = mount(
        <Column active={false} toolbarItems={toolbarItems} index={0} buttons={buttonsConfig} />
    );
    expect(column2.render()).toMatchSnapshot();
});
