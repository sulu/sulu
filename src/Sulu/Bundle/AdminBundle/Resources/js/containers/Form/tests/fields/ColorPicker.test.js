// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import ColorPicker from '../../fields/ColorPicker';
import ColorPickerComponent from '../../../../components/ColorPicker';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <ColorPicker
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(field.find(ColorPickerComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const onFinish = jest.fn();
    const onChange = jest.fn();

    const field = shallow(
        <ColorPicker
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={onChange}
            onFinish={onFinish}
            value="#123123"
        />
    );

    const component = field.find(ColorPickerComponent);
    expect(component.prop('valid')).toBe(true);
    expect(component.prop('onChange')).toBe(onChange);
    expect(component.prop('onBlur')).toBe(onFinish);
    expect(component.prop('disabled')).toBe(true);
});
