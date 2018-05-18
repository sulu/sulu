// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Phone from '../../fields/Phone';
import PhoneComponent from '../../../../components/Phone';

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <Phone
            error={error}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
        />
    );

    expect(field.find(PhoneComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const field = shallow(
        <Phone
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
        />
    );

    expect(field.find(PhoneComponent).prop('valid')).toBe(true);
});
