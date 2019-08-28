// @flow
import {mount, render} from 'enzyme';
import React from 'react';
import Select from '../Select';

const selectPropsMock = {
    label: 'Chose an option',
    onChange: () => {},
    options: [
        {
            value: 1,
            label: 'An option',
        },
    ],
    value: undefined,
};

test('Render select', () => {
    expect(render(<Select {...selectPropsMock} />)).toMatchSnapshot();
});

test('Render loading select', () => {
    expect(render(<Select {...selectPropsMock} loading={true} />)).toMatchSnapshot();
});

test('Render disabled select', () => {
    expect(render(
        <Select
            {...selectPropsMock}
            disabled={true}
        />
    )).toMatchSnapshot();
});

test('Render select with a prepended icon', () => {
    expect(render(
        <Select
            {...selectPropsMock}
            icon="fa-floppy-o"
        />
    )).toMatchSnapshot();
});

test('Render select without text', () => {
    expect(render(
        <Select
            {...selectPropsMock}
            showText={false}
        />
    )).toMatchSnapshot();
});

test('Render select with a different size', () => {
    expect(render(
        <Select
            {...selectPropsMock}
            size="small"
        />
    )).toMatchSnapshot();
});

test('Open select on click', () => {
    const select = mount(<Select {...selectPropsMock} />);

    expect(select.find('.optionList').length).toBe(0);
    select.find('.button').simulate('click');
    expect(select.find('.optionList').length).toBe(1);
});

test('Disabled select will not open', () => {
    const select = mount(
        <Select
            {...selectPropsMock}
            disabled={true}
        />
    );

    expect(select.find('.optionList').length).toBe(0);
    select.find('button').simulate('click');
    expect(select.find('.optionList').length).toBe(0);
});

test('Click on disabled option will not fire onChange', () => {
    const clickSpy = jest.fn();
    const propsMock = {
        label: 'Click to open',
        onChange: clickSpy,
        options: [
            {
                value: 1,
                label: 'An option',
                disabled: true,
            },
        ],
        value: undefined,
    };

    const select = mount(<Select {...propsMock} />);

    select.find('button').simulate('click');
    select.find('.option > button').first().simulate('click');

    expect(clickSpy).toHaveBeenCalledTimes(0);
});

test('Click on option fires onChange with the selected value as the first argument', () => {
    const clickSpy = jest.fn();
    const propsMock = {
        label: 'Click to open',
        onChange: clickSpy,
        options: [
            {
                value: 1,
                label: 'An option',
            },
            {
                value: 2,
                label: 'Another option',
            },
        ],
        value: undefined,
    };

    const select = mount(<Select {...propsMock} />);

    select.find('button').simulate('click');
    select.find('.option > button').first().simulate('click');

    expect(clickSpy.mock.calls[0][0]).toBe(1);
});

test('The label of the option is written in the toggle-button if you set the options value', () => {
    const clickSpy = jest.fn();
    const propsMock = {
        value: 2,
        label: 'Click to open',
        onChange: clickSpy,
        options: [
            {
                value: 1,
                label: 'An option',
            },
            {
                value: 2,
                label: 'Another option',
            },
        ],
    };

    const select = mount(<Select {...propsMock} />);

    expect(select.find('button').text()).toBe('Another option');
});
