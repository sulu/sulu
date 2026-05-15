// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import PasswordConfirmation from '../../fields/PasswordConfirmation';
import PasswordConfirmationComponent from '../../../../components/PasswordConfirmation';

jest.mock('../../../../components/PasswordConfirmation', () => jest.fn(() => null));

function getLatestPasswordConfirmationProps() {
    const calls = (PasswordConfirmationComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

test('Pass error correctly to PasswordConfirmation component', () => {
    const formInspector = ({locale: undefined}: any);
    const error = {keyword: 'required', parameters: {}};

    render(
        <PasswordConfirmation
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(getLatestPasswordConfirmationProps().valid).toBe(false);
});

test('Pass props correctly to PasswordConfirmation component', () => {
    const formInspector = ({locale: undefined}: any);
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <PasswordConfirmation
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const componentProps = getLatestPasswordConfirmationProps();
    expect(componentProps.valid).toBe(true);
    expect(componentProps.disabled).toBe(true);

    act(() => {
        componentProps.onChange('value');
    });

    expect(changeSpy).toBeCalledWith('value');
    expect(finishSpy).toBeCalledWith();
});
