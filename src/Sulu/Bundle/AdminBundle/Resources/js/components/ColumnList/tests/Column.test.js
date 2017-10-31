/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';
import Column from '../Column';

test('The component should render', () => {
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

    const toolbarItemConfigs = [
        {
            icon: 'plus',
            type: 'simple',
            onClick: () => {},
        },
        {
            icon: 'search',
            type: 'simple',
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
        <Column active={true} toolbarItemConfigs={toolbarItemConfigs} index={0} buttons={buttonsConfig} />
    );
    expect(column.render()).toMatchSnapshot();
});

test('The component should render', () => {
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

    const toolbarItemConfigs = [
        {
            icon: 'plus',
            type: 'simple',
            onClick: () => {},
        },
        {
            icon: 'search',
            type: 'simple',
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
        <Column active={true} toolbarItemConfigs={toolbarItemConfigs} index={0} buttons={buttonsConfig} />
    );
    expect(column.render()).toMatchSnapshot();
});
