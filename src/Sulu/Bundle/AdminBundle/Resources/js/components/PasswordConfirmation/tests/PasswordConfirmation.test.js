// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import PasswordConfirmation from '../PasswordConfirmation';

test('Should render two password fields', () => {
    expect(render(<PasswordConfirmation onChange={jest.fn()} />)).toMatchSnapshot();
});

test('Should only call onChange when both values match on blur', () => {
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
