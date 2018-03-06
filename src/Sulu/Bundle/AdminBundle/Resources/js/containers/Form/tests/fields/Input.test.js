// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Input from '../../fields/Input';
import InputComponent from '../../../../components/Input';

test('Pass error correctly to Input component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    const inputInvalid = shallow(
        <Input
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
            error={error}
        />
    );

    expect(inputInvalid.find(InputComponent).prop('valid')).toBe(false);

    const inputValid = shallow(
        <Input
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
        />
    );

    expect(inputValid.find(InputComponent).prop('valid')).toBe(true);
});
