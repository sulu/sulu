// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import debounce from 'debounce';
import PasswordConfirmation from '../PasswordConfirmation';

jest.mock('debounce', () => jest.fn((value) => value));

test('Should render two password fields and add a debounced function', () => {
    expect(render(<PasswordConfirmation disabled={true} onChange={jest.fn()} />)).toMatchSnapshot();
    expect(debounce).toBeCalledWith(expect.any(Function), 500);
});

test('Should render disabled input-components when disabled', () => {
    expect(render(<PasswordConfirmation onChange={jest.fn()} />)).toMatchSnapshot();
    expect(debounce).toBeCalledWith(expect.any(Function), 500);
});

test('Should only call onChange when both values match after the debounced time', () => {
    const changeSpy = jest.fn();
    const passwordConfirmation = shallow(<PasswordConfirmation onChange={changeSpy} />);

    expect(changeSpy).not.toBeCalled();

    passwordConfirmation.find('Input').at(0).simulate('change', 'asdf');
    passwordConfirmation.find('Input').at(1).simulate('change', 'jklö');

    expect(changeSpy).not.toBeCalled();

    passwordConfirmation.find('Input').at(1).simulate('change', 'asdf');
    passwordConfirmation.find('Input').at(1).simulate('blur');
    expect(changeSpy).toBeCalledWith('asdf');
});

test('Should mark the input fields as invalid if they do not match', () => {
    const changeSpy = jest.fn();
    const passwordConfirmation = shallow(<PasswordConfirmation onChange={changeSpy} />);

    expect(changeSpy).not.toBeCalled();

    passwordConfirmation.find('Input').at(0).simulate('change', 'asdf');
    passwordConfirmation.find('Input').at(1).simulate('change', 'jklö');

    passwordConfirmation.find('Input').at(1).simulate('blur');

    expect(passwordConfirmation.find('Input').at(0).prop('valid')).toBe(false);
    expect(passwordConfirmation.find('Input').at(1).prop('valid')).toBe(false);

    passwordConfirmation.find('Input').at(1).simulate('change', 'asdf');
    passwordConfirmation.find('Input').at(1).simulate('blur');

    expect(passwordConfirmation.find('Input').at(0).prop('valid')).toBe(true);
    expect(passwordConfirmation.find('Input').at(1).prop('valid')).toBe(true);
});

test('Should mark the input fields as invalid if the valid prop is false', () => {
    const changeSpy = jest.fn();
    const passwordConfirmation = shallow(<PasswordConfirmation onChange={changeSpy} valid={false} />);

    expect(passwordConfirmation.find('Input').at(0).prop('valid')).toBe(false);
    expect(passwordConfirmation.find('Input').at(1).prop('valid')).toBe(false);
});
