// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Input from '../../fields/Input';
import InputComponent from '../../../../components/Input';

test('Pass error correctly to Input component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    const inputInvalid = shallow(
        <Input
            error={error}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value="xyz"
        />
    );

    expect(inputInvalid.find(InputComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to Input component', () => {
    const inputValid = shallow(
        <Input
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value="xyz"
        />
    );

    expect(inputValid.find(InputComponent).prop('valid')).toBe(true);
});

test('Should not pass any arguments to onFinish callback', () => {
    const finishSpy = jest.fn();

    const input = shallow(
        <Input
            onChange={jest.fn()}
            onFinish={finishSpy}
            schemaPath=""
            value="xyz"
        />
    );

    input.find('Input').prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});
