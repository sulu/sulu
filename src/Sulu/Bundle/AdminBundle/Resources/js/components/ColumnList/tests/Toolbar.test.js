/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';
import Toolbar from '../Toolbar';

test('The component should render with active', () => {
    const toolbarItems = [
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

    const toolbar = mount(
        <Toolbar active={true} index={0} toolbarItems={toolbarItems} />
    );

    expect(toolbar.render()).toMatchSnapshot();
});

test('The component should render with active false', () => {
    const toolbarItems = [
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

    const toolbarActive = mount(
        <Toolbar active={false} index={0} toolbarItems={toolbarItems} />
    );

    expect(toolbarActive.render()).toMatchSnapshot();
});

test('The component should throw an exception when an unknown toolbar item type is given', () => {
    const toolbarItems = [
        {
            icon: 'plus',
            type: 'xxx-not-valid',
            onClick: () => {},
        },
    ];

    expect(() => {
        mount(<Toolbar active={false} index={0} toolbarItems={toolbarItems} />);
    }).toThrow(/xxx-not-valid/);
});
