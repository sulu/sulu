// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import Tooltip from '../Tooltip';
import Icon from '../../Icon';

test('The component should render in unfocused state', () => {
    const component = render(
        <Tooltip label="Copy">
            <button aria-label="Copy" type="button">
                <Icon name="su-copy" />
            </button>
        </Tooltip>
    );

    expect(component.find('Popover span').length).toBe(0);

    expect(component).toMatchSnapshot();
});

test('The component should render in focused state', () => {
    const component = mount(
        <Tooltip label="Copy">
            <button aria-label="Copy" type="button">
                <Icon name="su-copy" />
            </button>
        </Tooltip>
    );

    component.find('button').simulate('focus');

    expect(component.find('Popover span').text()).toBe('Copy');
    expect(component).toMatchSnapshot();
});

test('The component should render in hovered state', () => {
    const component = mount(
        <Tooltip label="Copy">
            <button aria-label="Copy" type="button">
                <Icon name="su-copy" />
            </button>
        </Tooltip>
    );

    component.find('Tooltip').simulate('mouseenter');

    expect(component.find('Popover span').text()).toBe('Copy');
    expect(component).toMatchSnapshot();

    component.find('Tooltip').simulate('mouseleave');
    expect(component.find('Popover span').length).toBe(0);
});
