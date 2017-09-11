/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import Dropdown from '../Dropdown';

const dropdownPropsMock = {
    label: 'Click to open',
    options: [
        {
            label: 'An option',
            onClick: () => {},
        },
    ],
};

test('Render dropdown', () => {
    expect(render(<Dropdown {...dropdownPropsMock} />)).toMatchSnapshot();
});

test('Render disabled dropdown', () => {
    expect(render(
        <Dropdown
            {...dropdownPropsMock}
            disabled={true}
        />
    )).toMatchSnapshot();
});

test('Render dropdown with a prepended icon', () => {
    expect(render(
        <Dropdown
            {...dropdownPropsMock}
            icon="floppy-o"
        />
    )).toMatchSnapshot();
});

test('Render dropdown with a different size', () => {
    expect(render(
        <Dropdown
            {...dropdownPropsMock}
            size="small"
        />
    )).toMatchSnapshot();
});

test('Open dropdown on click', () => {
    const dropdown = mount(<Dropdown {...dropdownPropsMock} />);

    expect(dropdown.find('.optionList').length).toBe(0);
    dropdown.find('.button').simulate('click');
    expect(dropdown.find('.optionList').length).toBe(1);
});

test('Disabled dropdown will not open', () => {
    const dropdown = mount(
        <Dropdown
            {...dropdownPropsMock}
            disabled={true}
        />
    );

    expect(dropdown.find('.optionList').length).toBe(0);
    dropdown.find('button').simulate('click');
    expect(dropdown.find('.optionList').length).toBe(0);
});

test('Click on option fires onClick', () => {
    const clickSpy = jest.fn();
    const propsMock = {
        label: 'Click to open',
        options: [
            {
                label: 'An option',
                onClick: clickSpy,
            },
        ],
    };

    const dropdown = mount(<Dropdown {...propsMock} />);

    dropdown.find('button').simulate('click');
    dropdown.find('.option > button').first().simulate('click');

    expect(clickSpy).toBeCalled();
});

test('Click on disabled option will not fire onClick', () => {
    const clickSpy = jest.fn();
    const propsMock = {
        label: 'Click to open',
        options: [
            {
                label: 'An option',
                onClick: clickSpy,
                disabled: true,
            },
        ],
    };

    const dropdown = mount(<Dropdown {...propsMock} />);

    dropdown.find('button').simulate('click');
    dropdown.find('.option > button').first().simulate('click');

    expect(clickSpy).toHaveBeenCalledTimes(0);
});
