// @flow
import React from 'react';
import {shallow} from 'enzyme';
import PasswordConfirmation from '../../fields/PasswordConfirmation';
import PasswordConfirmationComponent from '../../../../components/PasswordConfirmation';

test('Pass error correctly to PasswordConfirmation component', () => {
    const error = {keyword: 'required', parameters: {}};

    const passwordConfirmation = shallow(<PasswordConfirmation onChange={jest.fn()} error={error} value={undefined} />);

    expect(passwordConfirmation.find(PasswordConfirmationComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to PasswordConfirmation component', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const passwordConfirmation = shallow(
        <PasswordConfirmation onChange={changeSpy} onFinish={finishSpy} value={undefined} />
    );

    expect(passwordConfirmation.find(PasswordConfirmationComponent).prop('valid')).toBe(true);

    passwordConfirmation.find(PasswordConfirmationComponent).simulate('change', 'value');

    expect(changeSpy).toBeCalledWith('value');
    expect(finishSpy).toBeCalledWith();
});
