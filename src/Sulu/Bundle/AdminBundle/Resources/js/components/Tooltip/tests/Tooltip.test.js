// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Tooltip from '../Tooltip';
import Icon from '../../Icon';

test('The component should render in unfocused state', () => {
    const {asFragment} = render(
        <Tooltip label="Copy">
            <button aria-label="Copy" type="button">
                <Icon name="su-copy" />
            </button>
        </Tooltip>
    );

    expect(screen.queryByText('Copy')).not.toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('The component should render in focused state', async() => {
    const user = userEvent.setup();

    render(
        <Tooltip label="Copy">
            <button aria-label="Copy" type="button">
                <Icon name="su-copy" />
            </button>
        </Tooltip>
    );

    await user.tab();

    expect(screen.getByText('Copy')).toBeInTheDocument();
});

test('The component should render in hovered state', async() => {
    const user = userEvent.setup();

    render(
        <Tooltip label="Copy">
            <button aria-label="Copy" type="button">
                <Icon name="su-copy" />
            </button>
        </Tooltip>
    );

    const button = screen.getByRole('button', {name: 'Copy'});
    const tooltipContainer = button.parentElement;

    if (!tooltipContainer) {
        throw new Error('Expected tooltip container to be set');
    }

    await user.hover(tooltipContainer);
    expect(screen.getByText('Copy')).toBeInTheDocument();

    await user.unhover(tooltipContainer);
    expect(screen.queryByText('Copy')).not.toBeInTheDocument();
});
