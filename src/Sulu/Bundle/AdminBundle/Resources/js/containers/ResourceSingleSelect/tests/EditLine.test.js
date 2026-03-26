// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {fireEvent, render, screen} from '@testing-library/react';
import EditLine from '../EditLine';

test('Render an EditLine', () => {
    const {asFragment} = render(<EditLine id="1" onChange={jest.fn()} onRemove={jest.fn()} value="Test" />);
    expect(asFragment()).toMatchSnapshot();
});

test('Call onChange callback if input changes', () => {
    const changeSpy = jest.fn();
    render(<EditLine id={3} onChange={changeSpy} onRemove={jest.fn()} value="old" />);

    fireEvent.change(screen.getByRole('textbox'), {target: {value: 'new'}});

    expect(changeSpy).toHaveBeenLastCalledWith(3, 'new');
});

test('Call onRemove callback if line is removed', async() => {
    const removeSpy = jest.fn();
    const user = userEvent.setup();
    render(<EditLine id={3} onChange={jest.fn()} onRemove={removeSpy} value="old" />);

    await user.click(screen.getByRole('button'));

    expect(removeSpy).toBeCalledWith(3);
});
