// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Email from '../../fields/Email';
import EmailComponent from '../../../../components/Email';

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <Email
            error={error}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value={'xyz'}
        />
    );

    expect(field.find(EmailComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const field = shallow(
        <Email
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value={'xyz'}
        />
    );

    expect(field.find(EmailComponent).prop('valid')).toBe(true);
});
