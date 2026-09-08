// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import ProductFamilyAttributesField from '../../fields/ProductFamilyAttributesField';

const NEXT_VALUE = [{id: 'a1', required: true, variantSpecific: true}];

const SCHEMA_OPTIONS = {
    list_key: {name: 'list_key', value: 'attributes'},
    resource_key: {name: 'resource_key', value: 'attributes'},
};

// Stand in for the container: renders what it received, plus a button that fires onChange.
jest.mock('../../../Product/ProductFamilyAttributes', () => function ProductFamilyAttributes(props) {
    const React = require('react');

    return React.createElement(
        'div',
        {'data-testid': 'container'},
        JSON.stringify({
            dataPath: props.dataPath,
            disabled: props.disabled,
            hasFormInspector: !!props.formInspector,
            listKey: props.listKey,
            locale: props.locale,
            resourceKey: props.resourceKey,
            schemaPath: props.schemaPath,
            value: props.value,
        }),
        React.createElement(
            'button',
            {onClick: () => props.onChange(NEXT_VALUE), type: 'button'},
            'change'
        )
    );
});

jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../../stores/ResourceStore', () => jest.fn());

test('passes value, disabled and locale through to the container', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'attributes'));
    // $FlowFixMe
    formInspector.locale = observable.box('en');

    const value = [{id: 'a1', required: true, variantSpecific: false}];

    render(
        <ProductFamilyAttributesField
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={SCHEMA_OPTIONS}
            value={value}
        />
    );

    expect(screen.getByTestId('container')).toHaveTextContent('"disabled":true');
    expect(screen.getByTestId('container')).toHaveTextContent('"id":"a1"');
});

test('passes the schema options through to the container', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'attributes'));
    // $FlowFixMe
    formInspector.locale = observable.box('en');

    render(
        <ProductFamilyAttributesField
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={SCHEMA_OPTIONS}
            value={[]}
        />
    );

    expect(screen.getByTestId('container')).toHaveTextContent('"resourceKey":"attributes"');
    expect(screen.getByTestId('container')).toHaveTextContent('"listKey":"attributes"');
    expect(screen.getByTestId('container')).toHaveTextContent('"hasFormInspector":true');
    expect(screen.getByTestId('container')).toHaveTextContent('"dataPath":"/"');
    expect(screen.getByTestId('container')).toHaveTextContent('"schemaPath":"/"');
});

test.each(['resource_key', 'list_key'])('throws when the "%s" schema option is missing', (option) => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'attributes'));
    // $FlowFixMe
    formInspector.locale = observable.box('en');

    const schemaOptions = {...SCHEMA_OPTIONS};
    delete schemaOptions[option];

    expect(() => render(
        <ProductFamilyAttributesField
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={[]}
        />
    )).toThrow(option);
});

test('calls onChange and onFinish when the container changes the value', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'attributes'));
    // $FlowFixMe
    formInspector.locale = observable.box('en');

    const handleChange = jest.fn();
    const handleFinish = jest.fn();

    render(
        <ProductFamilyAttributesField
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={handleChange}
            onFinish={handleFinish}
            schemaOptions={SCHEMA_OPTIONS}
            value={[]}
        />
    );

    await userEvent.click(screen.getByText('change'));

    expect(handleChange).toHaveBeenCalledWith([{id: 'a1', required: true, variantSpecific: true}]);
    expect(handleFinish).toHaveBeenCalled();
});
