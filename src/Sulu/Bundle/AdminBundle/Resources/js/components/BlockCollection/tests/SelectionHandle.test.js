// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import SelectionHandle from '../SelectionHandle';

test('Render selection handle unchecked', () => {
    const {asFragment} = render(
        <SelectionHandle checked={false} onChange={jest.fn()} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render selection handle checked', () => {
    const {asFragment} = render(
        <SelectionHandle checked={true} onChange={jest.fn()} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Change checkbox should trigger onChange', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <SelectionHandle checked={true} onChange={changeSpy} />
    );

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toHaveBeenCalled();
});

test('Click on container should trigger onChange', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();
    const {container} = render(
        <SelectionHandle checked={true} onChange={changeSpy} />
    );

    const handle = container.firstElementChild;
    if (!handle) {
        throw new Error('Expected selection handle element');
    }

    await user.click(handle);

    expect(changeSpy).toHaveBeenCalled();
});
