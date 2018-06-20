// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ColorPicker from '../../fields/ColorPicker';
import ColorPickerComponent from '../../../../components/ColorPicker';

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <ColorPicker
            error={error}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value={'xyz'}
        />
    );

    expect(field.find(ColorPickerComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const onFinish = jest.fn();
    const onChange = jest.fn();

    const field = shallow(
        <ColorPicker
            onChange={onChange}
            onFinish={onFinish}
            schemaPath=""
            value={'#123123'}
        />
    );

    const component = field.find(ColorPickerComponent);
    expect(component.prop('valid')).toBe(true);
    expect(component.prop('onChange')).toBe(onChange);
    expect(component.prop('onBlur')).toBe(onFinish);
});
