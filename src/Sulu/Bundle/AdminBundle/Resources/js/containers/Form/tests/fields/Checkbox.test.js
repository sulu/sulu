// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Checkbox from '../../fields/Checkbox';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Render Toggler component as heading', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
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

    const {asFragment} = render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Pass the label correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{label: {name: 'label', title: 'Checkbox Title'}}}
        />
    );

    expect(screen.getByRole('checkbox', {name: 'Checkbox Title'})).toBeInTheDocument();
});

test('Pass disabled correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{label: {name: 'label', title: 'Checkbox Title'}}}
        />
    );

    expect(screen.getByRole('checkbox', {name: 'Checkbox Title'})).toBeDisabled();
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: 'not-boolean',
        },
    };

    expect(() => new Checkbox(({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions,
    }: any))).toThrow(/"default_value"/);
});

test('Set default value of null should not call onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Set default value if no value is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    expect(changeSpy).toHaveBeenCalledWith(false, {'isDefaultValue': true});
});

test('Do not set default value if a value is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Pass the value of true correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={true}
        />
    );

    expect(screen.getByRole('checkbox')).toBeChecked();
});

test('Pass the value of false correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={false}
        />
    );

    expect(screen.getByRole('checkbox')).not.toBeChecked();
});

test('Call onChange and onFinish on the changed callback of the Checkbox', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
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

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toHaveBeenCalledWith(true);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Pass the label correctly to Toggler component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
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

    expect(screen.getByRole('checkbox', {name: 'Toggler Title'})).toBeInTheDocument();
});

test('Pass disabled correctly to Toggler component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
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

    expect(screen.getByRole('checkbox', {name: 'Toggler Title'})).toBeDisabled();
});

test('Pass the value of true correctly to Toggler component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{type: {name: 'type', value: 'toggler'}}}
            value={true}
        />
    );

    expect(screen.getByRole('checkbox')).toBeChecked();
});

test('Pass the value of false correctly to Toggler component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{type: {name: 'type', value: 'toggler'}}}
            value={false}
        />
    );

    expect(screen.getByRole('checkbox')).not.toBeChecked();
});

test('Call onChange and onFinish on the changed callback of the Toggler', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
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

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toHaveBeenCalledWith(true);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Call onChange and onFinish on the changed callback of the Toggler with the header skin', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
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

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toHaveBeenCalledWith(true);
    expect(finishSpy).toHaveBeenCalledWith();
});
