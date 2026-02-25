// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import bindValueToOnChange from '../../../../utils/TestHelper/bindValueToOnChange';
import ColorPicker from '../../fields/ColorPicker';

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <ColorPicker
            {...createProps()}
            error={error}
        />
    );

    expect(screen.getByRole('textbox').closest('div')).toHaveClass('error');
});

test('Pass props correctly to component', () => {
    render(
        <ColorPicker
            {...createProps()}
            disabled={true}
            value="#123123"
        />
    );

    const input = screen.getByRole('textbox');

    expect(input).toBeDisabled();
    expect(input).toHaveDisplayValue('#123123');
    expect(input.closest('div')).not.toHaveClass('error');
});

test('Pass callbacks correctly to component', async() => {
    const user = userEvent.setup();
    const onFinish = jest.fn();
    const onChange = jest.fn();

    render(
        bindValueToOnChange(
            <ColorPicker
                {...createProps()}
                onChange={onChange}
                onFinish={onFinish}
                value=""
            />
        )
    );

    const input = screen.getByRole('textbox');
    await user.type(input, '#abc');
    expect(onChange).toHaveBeenLastCalledWith('#abc');

    await user.tab();
    expect(onFinish).toBeCalledWith();
});
