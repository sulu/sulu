// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import ColorPicker from '../../fields/ColorPicker';

let mockColorPickerProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/ColorPicker', () => jest.fn((props) => {
    mockColorPickerProps = props;

    return mockReact.createElement('input', {type: 'text'});
}));

beforeEach(() => {
    mockColorPickerProps = {};
});

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <ColorPicker
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(mockColorPickerProps.valid).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const onFinish = jest.fn();
    const onChange = jest.fn();

    render(
        <ColorPicker
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={onChange}
            onFinish={onFinish}
            value="#123123"
        />
    );

    expect(mockColorPickerProps.valid).toBe(true);
    expect(mockColorPickerProps.onChange).toBe(onChange);
    expect(mockColorPickerProps.onBlur).toBe(onFinish);
    expect(mockColorPickerProps.disabled).toBe(true);
});
