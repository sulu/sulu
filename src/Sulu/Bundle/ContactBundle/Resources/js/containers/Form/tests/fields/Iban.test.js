// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import Iban from '../../fields/Iban';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(),
    ResourceFormStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

test('Pass props correctly to Iban component', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const bic = shallow(
        <Iban {...fieldTypeDefaultProps} formInspector={formInspector} onChange={changeSpy} onFinish={finishSpy} />
    );

    expect(bic.props()).toEqual(expect.objectContaining({
        disabled: false,
        id: '/',
        onBlur: finishSpy,
        onChange: changeSpy,
        valid: true,
        value: undefined,
    }));
});

test('Pass disabled prop to Iban component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const bic = shallow(
        <Iban {...fieldTypeDefaultProps} disabled={true} formInspector={formInspector} />
    );

    expect(bic.prop('disabled')).toEqual(true);
});

test('Pass id prop to Iban component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const bic = shallow(
        <Iban {...fieldTypeDefaultProps} dataPath="/test" formInspector={formInspector} />
    );

    expect(bic.prop('id')).toEqual('/test');
});

test('Pass error to Iban component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const bic = shallow(
        <Iban {...fieldTypeDefaultProps} error={{}} formInspector={formInspector} />
    );

    expect(bic.prop('valid')).toEqual(false);
});

test('Pass value prop to Iban component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const bic = shallow(
        <Iban {...fieldTypeDefaultProps} formInspector={formInspector} value="Test" />
    );

    expect(bic.prop('value')).toEqual('Test');
});
