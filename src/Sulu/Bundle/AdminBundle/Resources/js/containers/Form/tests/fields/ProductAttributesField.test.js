// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import ProductAttributesField from '../../fields/ProductAttributesField';

const NEXT_VALUE = {'7': 12};

jest.mock('../../../Product/ProductAttributes', () => function ProductAttributes(props) {
    const React = require('react');

    return React.createElement(
        'div',
        {'data-testid': 'container'},
        JSON.stringify({
            dataPath: props.dataPath,
            disabled: props.disabled,
            error: props.error,
            hasFormInspector: !!props.formInspector,
            schemaPath: props.schemaPath,
            showAllErrors: props.showAllErrors,
            value: props.value,
            variant: props.variant,
        }),
        React.createElement(
            'button',
            {onClick: () => props.onChange(NEXT_VALUE), type: 'button'},
            'change'
        ),
        React.createElement(
            'button',
            {onClick: () => props.onFinish('/attributes/7', '/attributes'), type: 'button'},
            'finish'
        )
    );
});

jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../../stores/ResourceStore', () => jest.fn());

function renderField(props: Object = {}) {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'products'));

    return render(
        <ProductAttributesField
            {...fieldTypeDefaultProps}
            dataPath="/attributes"
            formInspector={formInspector}
            schemaPath="/attributes"
            value={{'7': null}}
            {...props}
        />
    );
}

test('passes value, paths, errors and disabled through to the container', () => {
    const error = {'7': {keyword: 'maximum', parameters: {}}};
    renderField({disabled: true, error, showAllErrors: true});

    const json = screen.getByTestId('container').textContent.replace('change', '').replace('finish', '');
    expect(JSON.parse(json)).toEqual({
        dataPath: '/attributes',
        disabled: true,
        error,
        hasFormInspector: true,
        schemaPath: '/attributes',
        showAllErrors: true,
        value: {'7': null},
        variant: false,
    });
});

test('reads the variant schema option', () => {
    renderField({schemaOptions: {variant: {name: 'variant', value: true}}});

    // eslint-disable-next-line jest-dom/prefer-to-have-text-content
    expect(screen.getByTestId('container').textContent).toContain('"variant":true');
});

test('throws when the variant schema option is not a boolean', () => {
    expect(() => renderField({schemaOptions: {variant: {name: 'variant', value: 'true'}}}))
        .toThrow('The "variant" schema option must be a boolean if given!');
});

test('calls onChange with the new value and onFinish when a row finishes', async() => {
    const onChange = jest.fn();
    const onFinish = jest.fn();
    renderField({onChange, onFinish});

    await userEvent.click(screen.getByText('change'));
    expect(onChange).toHaveBeenCalledWith(NEXT_VALUE);
    expect(onFinish).not.toHaveBeenCalled();

    await userEvent.click(screen.getByText('finish'));
    expect(onFinish).toHaveBeenCalledWith('/attributes/7', '/attributes');
});
