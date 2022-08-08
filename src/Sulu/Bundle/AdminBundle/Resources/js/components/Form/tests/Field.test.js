// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Field from '../Field';

test('Display a field with label', () => {
    const {container} = render(
        <Field label="Test" skin="dark">
            <p>Test</p>
        </Field>
    );
    expect(container).toMatchSnapshot();
});

test('Display a field with label and type', () => {
    const types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    const {container} = render(
        <Field label="Test" type={1} types={types}>
            <p>Test</p>
        </Field>
    );

    expect(container).toMatchSnapshot();
});

test('Display a field without label', () => {
    const {container} = render(
        <Field>
            <p>Test</p>
        </Field>
    );
    expect(container).toMatchSnapshot();
});

test('Display a field with colSpan and after space', () => {
    const {container} = render(
        <Field colSpan={7} spaceAfter={5}>
            <div>Test</div>
        </Field>
    );
    expect(container).toMatchSnapshot();
});

test('Display a field with description', () => {
    const {container} = render(
        <Field description="Testdescription">
            <div>Test</div>
        </Field>
    );
    expect(container).toMatchSnapshot();
});

test('Display a field with a required label', () => {
    const {container} = render(
        <Field label="Testlabel" required={true}>
            <div>Test</div>
        </Field>
    );
    expect(container).toMatchSnapshot();
});

test('Display a field with an error', () => {
    const {container} = render(
        <Field error="Error! Help!" label="Testlabel">
            <div>Test</div>
        </Field>
    );
    expect(container).toMatchSnapshot();
});

test('Change type of field', async() => {
    const typeChangeSpy = jest.fn();

    const types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    render(
        <Field label="Test" onTypeChange={typeChangeSpy} type={1} types={types}>
            <p>Test</p>
        </Field>
    );

    const field = screen.queryByText('Work');
    expect(screen.queryByTestId('backdrop')).not.toBeInTheDocument();
    await userEvent.click(field);
    expect(screen.getByTestId('backdrop')).toBeInTheDocument();

    const changeItem = screen.queryByText('Private');
    await userEvent.click(changeItem);
    expect(typeChangeSpy).toBeCalledWith(2);
    expect(screen.queryByTestId('backdrop')).not.toBeInTheDocument();
});
