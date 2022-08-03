// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import Popover from '../Popover';

test('Render a Popover', () => {
    const {container} = render(
        <Popover icon="su-calendar" label="Set time" size="small" skin="light">{() => 'Child'}</Popover>
    );
    expect(container).toMatchSnapshot();
});

test('Disable the Button if the Popover is disabled', () => {
    render(<Popover disabled={true} icon="su-calendar" label="Set time">{() => 'Child'}</Popover>);

    expect(screen.queryByRole('button')).toBeDisabled();
});

test('Show a loader if the Popover is loading', () => {
    const {container} = render(<Popover icon="su-calendar" label="Set time" loading={true}>{() => 'Child'}</Popover>);

    // eslint-disable-next-line testing-library/no-container, testing-library/no-node-access
    expect(container.querySelector('.loader')).toBeInTheDocument();
});

// test('Open popover on click', () => {
//     const dropdown = mount(<Popover label="Set time">{() => <h1>Test</h1>}</Popover>);

//     expect(dropdown.find('h1').length).toBe(0);
//     dropdown.find('button').simulate('click');
//     expect(dropdown.find('h1').length).toBe(1);
// });

// test('Disabled popover does not open on click', () => {
//     const dropdown = mount(<Popover disabled={true} label="Set time">{() => <h1>Test</h1>}</Popover>);

//     expect(dropdown.find('h1').length).toBe(0);
//     dropdown.find('button').simulate('click');
//     expect(dropdown.find('h1').length).toBe(0);
// });
