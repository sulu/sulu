// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import Bic from '../../fields/Bic';
import BicComponent from '../../../../components/Bic';

jest.mock('../../../../components/Bic', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector() {
    return ({}: any);
}

test('Pass props correctly to Bic component', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <Bic
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const [bicProps] = (BicComponent: any).mock.calls[0];
    expect(bicProps).toEqual(expect.objectContaining({
        disabled: false,
        id: '/',
        onBlur: finishSpy,
        onChange: changeSpy,
        valid: true,
        value: undefined,
    }));
});

test('Pass disabled prop to Bic component', () => {
    render(<Bic {...fieldTypeDefaultProps} disabled={true} formInspector={createFormInspector()} />);

    const [bicProps] = (BicComponent: any).mock.calls[0];
    expect(bicProps.disabled).toEqual(true);
});

test('Pass id prop to Bic component', () => {
    render(<Bic {...fieldTypeDefaultProps} dataPath="/test" formInspector={createFormInspector()} />);

    const [bicProps] = (BicComponent: any).mock.calls[0];
    expect(bicProps.id).toEqual('/test');
});

test('Pass error to Bic component', () => {
    render(<Bic {...fieldTypeDefaultProps} error={{}} formInspector={createFormInspector()} />);

    const [bicProps] = (BicComponent: any).mock.calls[0];
    expect(bicProps.valid).toEqual(false);
});

test('Pass value prop to Bic component', () => {
    render(<Bic {...fieldTypeDefaultProps} formInspector={createFormInspector()} value="Test" />);

    const [bicProps] = (BicComponent: any).mock.calls[0];
    expect(bicProps.value).toEqual('Test');
});
