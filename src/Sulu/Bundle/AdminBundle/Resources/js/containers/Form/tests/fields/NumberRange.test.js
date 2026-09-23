// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import NumberRange from '../../fields/NumberRange';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../../stores/ResourceStore', () => jest.fn());

function renderField(props: Object = {}) {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));

    return render(
        <NumberRange
            {...fieldTypeDefaultProps}
            dataPath="/temperature"
            formInspector={formInspector}
            {...props}
        />
    );
}

function fromInput(): HTMLInputElement {
    // $FlowFixMe: the placeholder belongs to an input
    return screen.getByPlaceholderText('sulu_admin.from');
}

function toInput(): HTMLInputElement {
    // $FlowFixMe: the placeholder belongs to an input
    return screen.getByPlaceholderText('sulu_admin.until');
}

test('Render both bounds of the value', () => {
    renderField({value: {from: -20, to: 60}});

    expect(fromInput().value).toBe('-20');
    expect(toInput().value).toBe('60');
    expect(fromInput().id).toBe('/temperature');
});

test('Render empty inputs without a value', () => {
    renderField({value: undefined});

    expect(fromInput().value).toBe('');
    expect(toInput().value).toBe('');
});

test('Pass min, max and step to both inputs', () => {
    renderField({
        schemaOptions: {
            max: {name: 'max', value: '150'},
            min: {name: 'min', value: '-50'},
            step: {name: 'step', value: '0.5'},
        },
    });

    [fromInput(), toInput()].forEach((input) => {
        expect(input.min).toBe('-50');
        expect(input.max).toBe('150');
        expect(input.step).toBe('0.5');
    });
});

test('Call onChange with the changed bound and keep the other', async() => {
    const changeSpy = jest.fn();
    renderField({onChange: changeSpy, value: {from: -20, to: undefined}});

    await userEvent.type(toInput(), '8');

    expect(changeSpy).toHaveBeenLastCalledWith({from: -20, to: 8});
});

test('Call onChange with a half filled range while the other bound is empty', async() => {
    const changeSpy = jest.fn();
    renderField({onChange: changeSpy, value: {from: -20, to: 60}});

    await userEvent.clear(toInput());

    expect(changeSpy).toHaveBeenLastCalledWith({from: -20, to: null});
});

test('Call onChange with null once both bounds are emptied', async() => {
    const changeSpy = jest.fn();
    renderField({onChange: changeSpy, value: {from: 5, to: null}});

    await userEvent.clear(fromInput());

    expect(changeSpy).toHaveBeenLastCalledWith(null);
});

test('Call onFinish without arguments when an input loses focus', () => {
    const finishSpy = jest.fn();
    renderField({onFinish: finishSpy});

    fireEvent.blur(fromInput());
    fireEvent.blur(toInput());

    expect(finishSpy).toHaveBeenCalledTimes(2);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Disable both inputs', () => {
    renderField({disabled: true});

    expect(fromInput().disabled).toBe(true);
    expect(toInput().disabled).toBe(true);
});

function hasError(input: HTMLInputElement): boolean {
    return !!input.closest('.error');
}

test('Mark only the input of a bound with an error', () => {
    renderField({error: {to: {keyword: 'maximum', parameters: {}}}});

    expect(hasError(fromInput())).toBe(false);
    expect(hasError(toInput())).toBe(true);
});

test('Mark both inputs with an error of the whole value', () => {
    renderField({error: {keyword: 'anyOf', parameters: {}}});

    expect(hasError(fromInput())).toBe(true);
    expect(hasError(toInput())).toBe(true);
});

test('Mark no input without an error', () => {
    renderField({error: undefined});

    expect(hasError(fromInput())).toBe(false);
    expect(hasError(toInput())).toBe(false);
});
