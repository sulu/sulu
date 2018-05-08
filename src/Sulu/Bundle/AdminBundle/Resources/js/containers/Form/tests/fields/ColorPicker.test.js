// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ColorPicker from '../../fields/ColorPicker';
import ColorPickerComponent from '../../../../components/ColorPicker';

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <ColorPicker
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
            error={error}
        />
    );

    expect(field.find(ColorPickerComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const field = shallow(
        <ColorPicker
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
        />
    );

    expect(field.find(ColorPickerComponent).prop('valid')).toBe(true);
});
