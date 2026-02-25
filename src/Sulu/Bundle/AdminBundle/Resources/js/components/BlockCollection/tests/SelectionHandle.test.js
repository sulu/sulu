// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SelectionHandle from '../SelectionHandle';

test('Render selection handle unchecked', () => {
    const {asFragment} = render(<SelectionHandle checked={false} onChange={jest.fn()} />);
    const checkbox = screen.getByRole('checkbox');

    expect(checkbox).not.toBeChecked();
    expect(checkbox.closest('span')).toHaveClass('dark');
    expect(asFragment()).toMatchSnapshot();
});

test('Render selection handle checked', () => {
    render(<SelectionHandle checked={true} onChange={jest.fn()} />);
    const checkbox = screen.getByRole('checkbox');

    expect(checkbox).toBeChecked();
    expect(checkbox.closest('span')).toHaveClass('light');
});

test('Change checkbox should trigger onChange', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    render(<SelectionHandle checked={true} onChange={changeSpy} />);

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toBeCalled();
});

test('Click on container should trigger onChange', () => {
    const changeSpy = jest.fn();
    const stopPropagationSpy = jest.spyOn(Event.prototype, 'stopPropagation');
    render(<SelectionHandle checked={true} onChange={changeSpy} />);
    const checkbox = screen.getByRole('checkbox');

    const container = checkbox.closest('div');
    if (!container) {
        throw new Error('Expected checkbox container element');
    }

    container.click();

    expect(stopPropagationSpy).toBeCalled();
    expect(changeSpy).toBeCalled();
    stopPropagationSpy.mockRestore();
});
