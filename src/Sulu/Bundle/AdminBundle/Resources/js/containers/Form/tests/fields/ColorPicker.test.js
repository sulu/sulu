// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import ColorPicker from '../../fields/ColorPicker';
import ColorPickerComponent from '../../../../components/ColorPicker';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <ColorPicker
            error={error}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            value={'xyz'}
        />
    );

    expect(field.find(ColorPickerComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const onFinish = jest.fn();
    const onChange = jest.fn();

    const field = shallow(
        <ColorPicker
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={onChange}
            onFinish={onFinish}
            schemaPath=""
            showAllErrors={false}
            value={'#123123'}
        />
    );

    const component = field.find(ColorPickerComponent);
    expect(component.prop('valid')).toBe(true);
    expect(component.prop('onChange')).toBe(onChange);
    expect(component.prop('onBlur')).toBe(onFinish);
});
