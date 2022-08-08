// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import debounce from 'debounce';
import PasswordConfirmation from '../PasswordConfirmation';

jest.mock('debounce', () => jest.fn((value) => value));

test('Should render two password fields and add a debounced function', () => {
    const {container} = render(<PasswordConfirmation disabled={true} onChange={jest.fn()} />);
    expect(container).toMatchSnapshot();
    expect(debounce).toBeCalledWith(expect.any(Function), 500);
});

test('Should render disabled input-components when disabled', () => {
    const {container} = render(<PasswordConfirmation onChange={jest.fn()} />);
    expect(container).toMatchSnapshot();
    expect(debounce).toBeCalledWith(expect.any(Function), 500);
});

test('Should only call onChange when both values match after the debounced time', async() => {
    const changeSpy = jest.fn();
    render(<PasswordConfirmation onChange={changeSpy} />);

    const inputs = screen.queryAllByDisplayValue('');

    expect(changeSpy).not.toBeCalled();

    await userEvent.type(inputs[0], 'asdf');
    await userEvent.type(inputs[1], 'jklö');

    expect(changeSpy).not.toBeCalled();

    await userEvent.clear(inputs[1]);
    await userEvent.type(inputs[1], 'asdf');

    expect(changeSpy).toBeCalledWith('asdf');
});

test('Should mark the input fields as invalid if they do not match', async() => {
    const changeSpy = jest.fn();
    const {container} = render(<PasswordConfirmation onChange={changeSpy} />);

    const inputs = screen.queryAllByDisplayValue('');

    expect(changeSpy).not.toBeCalled();

    await userEvent.type(inputs[0], 'asdf');
    await userEvent.type(inputs[1], 'jklö');
    await userEvent.tab(); // tab away from input
    //await userEvent.blur(inputs[1]);

    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.error')).toBeInTheDocument();

    await userEvent.clear(inputs[1]);
    await userEvent.type(inputs[1], 'asdf');

    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.error')).not.toBeInTheDocument();
});

test('Should mark the input fields as invalid if the valid prop is false', () => {
    const changeSpy = jest.fn();
    const {container} = render(<PasswordConfirmation onChange={changeSpy} valid={false} />);

    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.error')).toBeInTheDocument();
});
