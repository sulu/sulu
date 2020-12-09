// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import Popover from '../Popover';

test('Render a Popover', () => {
    expect(render(
        <Popover icon="su-calendar" label="Set time" size="small">{() => 'Child'}</Popover>
    )).toMatchSnapshot();
});

test('Disable the Button if the Popover is disabled', () => {
    const popover = mount(<Popover disabled={true} icon="su-calendar" label="Set time">{() => 'Child'}</Popover>);

    expect(popover.find('button').prop('disabled')).toEqual(true);
});

test('Show a loader if the Popover is loading', () => {
    const popover = mount(<Popover icon="su-calendar" label="Set time" loading={true}>{() => 'Child'}</Popover>);

    expect(popover.find('Loader')).toHaveLength(1);
});

test('Open popover on click', () => {
    const dropdown = mount(<Popover label="Set time">{() => <h1>Test</h1>}</Popover>);

    expect(dropdown.find('h1').length).toBe(0);
    dropdown.find('button').simulate('click');
    expect(dropdown.find('h1').length).toBe(1);
});

test('Disabled popover does not open on click', () => {
    const dropdown = mount(<Popover disabled={true} label="Set time">{() => <h1>Test</h1>}</Popover>);

    expect(dropdown.find('h1').length).toBe(0);
    dropdown.find('button').simulate('click');
    expect(dropdown.find('h1').length).toBe(0);
});
