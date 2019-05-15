// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import Field from '../Field';

test('Display a field with label', () => {
    expect(render(
        <Field label="Test">
            <p>Test</p>
        </Field>
    )).toMatchSnapshot();
});

test('Display a field with label and type', () => {
    const types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    expect(render(
        <Field label="Test" type={1} types={types}>
            <p>Test</p>
        </Field>
    )).toMatchSnapshot();
});

test('Display a field without label', () => {
    expect(render(
        <Field>
            <p>Test</p>
        </Field>
    )).toMatchSnapshot();
});

test('Display a field with colSpan and after space', () => {
    expect(render(
        <Field colSpan={7} spaceAfter={5}>
            <div>Test</div>
        </Field>
    )).toMatchSnapshot();
});

test('Display a field with description', () => {
    expect(render(
        <Field description="Testdescription">
            <div>Test</div>
        </Field>
    )).toMatchSnapshot();
});

test('Display a field with a required label', () => {
    expect(render(
        <Field label="Testlabel" required={true}>
            <div>Test</div>
        </Field>
    )).toMatchSnapshot();
});

test('Display a field with an error', () => {
    expect(render(
        <Field error="Error! Help!" label="Testlabel">
            <div>Test</div>
        </Field>
    )).toMatchSnapshot();
});

test('Change type of field', () => {
    const typeChangeSpy = jest.fn();

    const types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    const field = mount(
        <Field label="Test" onTypeChange={typeChangeSpy} type={1} types={types}>
            <p>Test</p>
        </Field>
    );

    expect(field.find('ArrowMenu').prop('open')).toEqual(false);
    field.find('button').simulate('click');
    expect(field.find('ArrowMenu').prop('open')).toEqual(true);

    field.find('ArrowMenu Item').at(1).simulate('click');
    expect(typeChangeSpy).toBeCalledWith(2);
    expect(field.find('ArrowMenu').prop('open')).toEqual(false);
});
