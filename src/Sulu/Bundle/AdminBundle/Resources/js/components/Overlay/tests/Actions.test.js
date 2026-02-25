// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Actions from '../Actions';

test('The component should render', () => {
    const actions = [
        {title: 'Action 1', onClick: () => {}},
        {title: 'Action 2', onClick: () => {}},
    ];
    const {asFragment} = render(<Actions actions={actions} />);

    expect(asFragment()).toMatchSnapshot();
});

test('The component should call the corresponding callback when an action is clicked', async() => {
    const actions = [
        {title: 'Action 1', onClick: jest.fn()},
        {title: 'Action 2', onClick: jest.fn()},
    ];
    render(<Actions actions={actions} />);

    await userEvent.click(screen.getByRole('button', {name: 'Action 1'}));

    expect(actions[0].onClick).toBeCalled();
    expect(actions[1].onClick).not.toBeCalled();
});
