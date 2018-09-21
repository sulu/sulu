// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import Phone from '../../fields/Phone';
import PhoneComponent from '../../../../components/Phone';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <Phone
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(field.find(PhoneComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const field = shallow(
        <Phone
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(field.find(PhoneComponent).prop('valid')).toBe(true);
});
