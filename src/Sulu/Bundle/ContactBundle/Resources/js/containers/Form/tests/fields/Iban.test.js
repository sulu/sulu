// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import Iban from '../../fields/Iban';
import IbanComponent from '../../../../components/Iban';

jest.mock('../../../../components/Iban', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector() {
    return ({}: any);
}

test('Pass props correctly to Iban component', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <Iban
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const [ibanProps] = (IbanComponent: any).mock.calls[0];
    expect(ibanProps).toEqual(expect.objectContaining({
        disabled: false,
        id: '/',
        onBlur: finishSpy,
        onChange: changeSpy,
        valid: true,
        value: undefined,
    }));
});

test('Pass disabled prop to Iban component', () => {
    render(<Iban {...fieldTypeDefaultProps} disabled={true} formInspector={createFormInspector()} />);

    const [ibanProps] = (IbanComponent: any).mock.calls[0];
    expect(ibanProps.disabled).toEqual(true);
});

test('Pass id prop to Iban component', () => {
    render(<Iban {...fieldTypeDefaultProps} dataPath="/test" formInspector={createFormInspector()} />);

    const [ibanProps] = (IbanComponent: any).mock.calls[0];
    expect(ibanProps.id).toEqual('/test');
});

test('Pass error to Iban component', () => {
    render(<Iban {...fieldTypeDefaultProps} error={{}} formInspector={createFormInspector()} />);

    const [ibanProps] = (IbanComponent: any).mock.calls[0];
    expect(ibanProps.valid).toEqual(false);
});

test('Pass value prop to Iban component', () => {
    render(<Iban {...fieldTypeDefaultProps} formInspector={createFormInspector()} value="Test" />);

    const [ibanProps] = (IbanComponent: any).mock.calls[0];
    expect(ibanProps.value).toEqual('Test');
});
