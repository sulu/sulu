// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ColorPicker from '../../fields/ColorPicker';
import ColorPickerComponent from '../../../../components/ColorPicker';

jest.mock('../../../../components/ColorPicker', () => jest.fn(() => null));

function getLatestColorPickerProps() {
    const calls = (ColorPickerComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

test('Pass error correctly to component', () => {
    const formInspector = ({locale: undefined}: any);
    const error = {keyword: 'minLength', parameters: {}};

    render(<ColorPicker {...fieldTypeDefaultProps} error={error} formInspector={formInspector} />);

    expect(getLatestColorPickerProps().valid).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = ({locale: undefined}: any);
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

    const componentProps = getLatestColorPickerProps();
    expect(componentProps.valid).toBe(true);
    expect(componentProps.onChange).toBe(onChange);
    expect(componentProps.onBlur).toBe(onFinish);
    expect(componentProps.disabled).toBe(true);
});
