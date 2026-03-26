// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import FormInspector from '../../FormInspector';
import Checkbox from '../../fields/Checkbox';
import Heading from '../../fields/Heading';
import CheckboxComponent from '../../../../components/Checkbox';
import Toggler from '../../../../components/Toggler';

jest.mock('../../fields/Heading', () => jest.fn(({children}) => <div>{children}</div>));
jest.mock('../../../../components/Checkbox', () => jest.fn(() => null));
jest.mock('../../../../components/Toggler', () => jest.fn(() => null));

function getLatestCheckboxProps() {
    const calls = (CheckboxComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestTogglerProps() {
    const calls = (Toggler: any).mock.calls;
    return calls[calls.length - 1][0];
}

function createFormInspector() {
    return new FormInspector(({}: any));
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render Toggler component as heading', () => {
    const formInspector = createFormInspector();
    const schemaOptions = {
        description: {
            name: 'description',
            title: 'Hides a block',
        },
        icon: {
            name: 'icon',
            value: 'su-eye',
        },
        label: {
            name: 'label',
            title: 'Hide block',
        },
        skin: {
            name: 'skin',
            value: 'heading',
        },
    };

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect((Heading: any).mock.calls).toHaveLength(1);
    expect((CheckboxComponent: any).mock.calls).toHaveLength(1);
});

test('Pass the label correctly to Checkbox component', () => {
    const formInspector = createFormInspector();
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{label: {name: 'label', title: 'Checkbox Title'}}}
        />
    );
    expect(getLatestCheckboxProps().children).toEqual('Checkbox Title');
});

test('Pass disabled correctly to Checkbox component', () => {
    const formInspector = createFormInspector();
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{label: {name: 'label', title: 'Checkbox Title'}}}
        />
    );
    expect(getLatestCheckboxProps().disabled).toEqual(true);
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const formInspector = createFormInspector();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: 'not-boolean',
        },
    };

    expect(() => render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrow(/"default_value"/);
});

test('Set default value of null should not call onChange', () => {
    const formInspector = createFormInspector();
    const changeSpy = jest.fn();

    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: null,
        },
    };

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Set default value if no value is passed', () => {
    const formInspector = createFormInspector();
    const changeSpy = jest.fn();

    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: false,
        },
    };

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith(false, {'isDefaultValue': true});
});

test('Do not set default value if a value is passed', () => {
    const formInspector = createFormInspector();
    const changeSpy = jest.fn();

    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: false,
        },
    };

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
            value={false}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Pass the value of true correctly to Checkbox component', () => {
    const formInspector = createFormInspector();
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={true}
        />
    );
    expect(getLatestCheckboxProps().checked).toEqual(true);
});

test('Pass the value of false correctly to Checkbox component', () => {
    const formInspector = createFormInspector();
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={false}
        />
    );
    expect(getLatestCheckboxProps().checked).toEqual(false);
});

test('Call onChange and onFinish on the changed callback of the Checkbox', () => {
    const formInspector = createFormInspector();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );
    getLatestCheckboxProps().onChange(true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});

test('Pass the label correctly to Toggler component', () => {
    const formInspector = createFormInspector();
    const schemaOptions = {
        label: {name: 'label', title: 'Toggler Title'},
        type: {name: 'type', value: 'toggler'},
    };

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            label="Test"
            schemaOptions={schemaOptions}
        />
    );
    expect(getLatestTogglerProps().children).toEqual('Toggler Title');
});

test('Pass disabled correctly to Toggler component', () => {
    const formInspector = createFormInspector();
    const schemaOptions = {
        label: {name: 'label', title: 'Toggler Title'},
        type: {name: 'type', value: 'toggler'},
    };

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            label="Test"
            schemaOptions={schemaOptions}
        />
    );
    expect(getLatestTogglerProps().disabled).toEqual(true);
});

test('Pass the value of true correctly to Toggler component', () => {
    const formInspector = createFormInspector();
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{type: {name: 'type', value: 'toggler'}}}
            value={true}
        />
    );
    expect(getLatestTogglerProps().checked).toEqual(true);
});

test('Pass the value of false correctly to Toggler component', () => {
    const formInspector = createFormInspector();
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{type: {name: 'type', value: 'toggler'}}}
            value={false}
        />
    );
    expect(getLatestTogglerProps().checked).toEqual(false);
});

test('Call onChange and onFinish on the changed callback of the Toggler', () => {
    const formInspector = createFormInspector();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={{type: {name: 'type', value: 'toggler'}}}
        />
    );
    getLatestTogglerProps().onChange(true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});

test('Call onChange and onFinish on the changed callback of the Toggler with the header skin', () => {
    const formInspector = createFormInspector();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={{skin: {name: 'skin', value: 'heading'}, type: {name: 'type', value: 'toggler'}}}
        />
    );
    getLatestTogglerProps().onChange(true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});
