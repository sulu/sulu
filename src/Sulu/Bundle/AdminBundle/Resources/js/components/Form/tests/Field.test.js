// @flow
import React from 'react';
import {render} from 'enzyme';
import Field from '../Field';

test('Display a field with label', () => {
    expect(render(
        <Field label="Test">
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
