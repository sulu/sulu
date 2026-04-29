// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
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

test('The component should render in focused state', () => {
    const {asFragment} = render(
        <Tooltip label="Copy">
            <button aria-label="Copy" type="button">
                <Icon name="su-copy" />
            </button>
        </Tooltip>
    );

    fireEvent.focus(screen.getByRole('button'));

    expect(screen.getByText('Copy')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('The component should render in hovered state', async() => {
    const user = userEvent.setup();
    const {asFragment} = render(
        <Tooltip label="Copy">
            <button aria-label="Copy" type="button">
                <Icon name="su-copy" />
            </button>
        </Tooltip>
    );

    const button = screen.getByRole('button');
    const tooltipContainer = button.parentElement;

    if (!tooltipContainer) {
        throw new Error('Expected tooltip container');
    }

    await user.hover(tooltipContainer);
    expect(screen.getByText('Copy')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();

    await user.unhover(tooltipContainer);
    expect(screen.queryByText('Copy')).not.toBeInTheDocument();
});
