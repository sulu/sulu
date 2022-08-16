// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.loader')).toBeInTheDocument();
});

test('Open popover on click', async() => {
    render(<Popover label="Set time">{() => <h1>Test</h1>}</Popover>);

    expect(screen.queryByRole('heading')).not.toBeInTheDocument();
    await userEvent.click(screen.queryByRole('button'));
    expect(screen.getByRole('heading')).toBeInTheDocument();
});

test('Disabled popover does not open on click', async() => {
    render(<Popover disabled={true} label="Set time">{() => <h1>Test</h1>}</Popover>);

    expect(screen.queryByRole('heading')).not.toBeInTheDocument();
    await userEvent.click(screen.queryByRole('button'));
    expect(screen.queryByRole('heading')).not.toBeInTheDocument();
});
