// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Button from '../Button';

test('Should render with icon and disabled', () => {
    const {asFragment} = render(<Button disabled={true} icon="su-plus-circle" onClick={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should call the callback on click', async() => {
    const onClick = jest.fn();
    render(<Button icon="su-plus-circle" onClick={onClick} />);

    await userEvent.click(screen.getByRole('button'));
    expect(onClick).toBeCalled();
});
