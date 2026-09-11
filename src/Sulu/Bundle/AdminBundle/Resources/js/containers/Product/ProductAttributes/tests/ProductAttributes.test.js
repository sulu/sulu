// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import metadataStore from '../../../Form/stores/metadataStore';
import FormInspector from '../../../Form/FormInspector';
import ProductAttributes from '../ProductAttributes';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../Form/stores/metadataStore', () => ({
    getJsonSchema: jest.fn(),
    getSchema: jest.fn(),
}));

// Loader has no accessible role, so stand in with a marker.
jest.mock('../../../../components/Loader', () => function Loader() {
    const React = require('react');

    return React.createElement('div', {'data-testid': 'loader'});
});

// The inner form inspector is the real one; only the host form's inspector is mocked.
// jest.mock factories cannot close over imports, so mobx is required inside.
jest.mock('../../../Form/FormInspector', () => {
    const {observable} = require('mobx');
    const RealFormInspector = jest.requireActual('../../../Form/FormInspector').default;

    return jest.fn(function(formStore) {
        if (formStore) {
            return new RealFormInspector(formStore);
        }

        this.family = observable.box('family-1');
        this.locale = observable.box('en');
        this.options = {};
        this.getValueByPath = jest.fn((path) => path === '/productFamily' ? this.family.get() : undefined);
        this.isFieldModified = jest.fn(() => false);
    });
});

let rendererProps: Object = {};

// The toolbar renders inside the renderer, so the props go into their own element to stay parseable.
jest.mock('../ProductAttributesRenderer', () => function ProductAttributesRenderer(props) {
    const React = require('react');
    rendererProps = props;

    return React.createElement(
        'div',
        {'data-testid': 'renderer'},
        React.createElement('span', {'data-testid': 'renderer-props'}, JSON.stringify({
            data: props.data,
            disabled: props.disabled,
            errors: props.errors,
            schema: props.schema,
        })),
        props.toolbar,
        React.createElement(
            'button',
            {onClick: () => props.onChange('attribute_7', 5), type: 'button'},
            'change'
        ),
        React.createElement(
            'button',
            {
                onClick: () => props.onFinish('/attribute_7', '/attribute_group_1/items/attribute_7'),
                type: 'button',
            },
            'finish'
        )
    );
});

const SCHEMA = {
    attribute_group_1: {items: {attribute_7: {label: 'Weight', type: 'number'}}, label: 'Dimensions', type: 'section'},
};

const JSON_SCHEMA = {
    properties: {attribute_7: {maximum: 10, type: 'number'}},
    required: ['attribute_7'],
    type: 'object',
};

function deferred() {
    // eslint-disable-next-line no-unused-vars
    let resolve = (value: mixed) => {};
    // eslint-disable-next-line no-unused-vars
    let reject = (reason: mixed) => {};
    const promise = new Promise((res, rej) => {
        resolve = res;
        reject = rej;
    });

    return {promise, reject, resolve};
}

function renderComponent(props: Object = {}) {
    // $FlowFixMe
    const formInspector: FormInspector = new FormInspector();

    const view = render(
        <ProductAttributes
            dataPath="/attributes"
            disabled={false}
            formInspector={formInspector}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            router={undefined}
            schemaPath="/attributes"
            showAllErrors={false}
            value={{'7': null}}
            variant={false}
            {...props}
        />
    );

    return {...view, formInspector};
}

async function renderLoaded(props: Object = {}) {
    metadataStore.getSchema.mockResolvedValue(SCHEMA);
    metadataStore.getJsonSchema.mockResolvedValue(JSON_SCHEMA);
    const view = renderComponent(props);
    expect(await screen.findByTestId('renderer')).toBeInTheDocument();

    return view;
}

beforeEach(() => {
    rendererProps = {};
    metadataStore.getSchema.mockReset();
    metadataStore.getJsonSchema.mockReset();
    metadataStore.getJsonSchema.mockResolvedValue({type: 'object'});
});

test('shows a loader until the metadata resolved, then the renderer with the inner store', async() => {
    const request = deferred();
    metadataStore.getSchema.mockReturnValue(request.promise);

    renderComponent({value: {'7': 3, '8': null}});

    expect(metadataStore.getSchema).toHaveBeenCalledWith('product_attributes', undefined, {productFamily: 'family-1'});
    expect(metadataStore.getJsonSchema)
        .toHaveBeenCalledWith('product_attributes', undefined, {productFamily: 'family-1'});
    expect(screen.getByTestId('loader')).toBeInTheDocument();
    expect(screen.queryByTestId('renderer')).not.toBeInTheDocument();

    await act(async() => {
        request.resolve(SCHEMA);
        await request.promise;
    });

    expect(screen.queryByTestId('loader')).not.toBeInTheDocument();
    expect(JSON.parse(screen.getByTestId('renderer-props').textContent)).toEqual({
        data: {attribute_7: 3, attribute_8: null},
        disabled: false,
        errors: {},
        schema: SCHEMA,
    });
    expect(rendererProps.formInspector.getValueByPath('/attribute_7')).toEqual(3);
    expect(rendererProps.formInspector.locale.get()).toEqual('en');
});

test('sends the variant flag', () => {
    metadataStore.getSchema.mockReturnValue(deferred().promise);

    renderComponent({variant: true});

    expect(metadataStore.getSchema)
        .toHaveBeenCalledWith('product_attributes', undefined, {productFamily: 'family-1', variant: true});
});

test('falls back to the parent product from the form options', () => {
    metadataStore.getSchema.mockReturnValue(deferred().promise);
    const FormInspectorMock = require('../../../Form/FormInspector');
    // $FlowFixMe
    FormInspectorMock.mockImplementationOnce(function() {
        this.options = {parentId: 'product-1'};
        this.getValueByPath = jest.fn(() => undefined);
        this.isFieldModified = jest.fn(() => false);
    });

    renderComponent({variant: true});

    expect(metadataStore.getSchema)
        .toHaveBeenCalledWith('product_attributes', undefined, {product: 'product-1', variant: true});
});

test('creates a new store when the family changes', async() => {
    const {formInspector} = await renderLoaded();
    const firstInspector = rendererProps.formInspector;

    act(() => {
        // $FlowFixMe
        formInspector.family.set('family-2');
    });

    expect(metadataStore.getSchema).toHaveBeenCalledTimes(2);
    // eslint-disable-next-line max-len
    expect(metadataStore.getSchema).toHaveBeenLastCalledWith('product_attributes', undefined, {productFamily: 'family-2'});
    expect(await screen.findByTestId('renderer')).toBeInTheDocument();
    expect(rendererProps.formInspector).not.toBe(firstInspector);
});

test('writes a changed row into the inner store and emits the map keyed by id', async() => {
    const onChange = jest.fn();
    await renderLoaded({onChange, value: {'7': null, '42': 'keep'}});

    await userEvent.click(screen.getByText('change'));

    expect(rendererProps.formInspector.getValueByPath('/attribute_7')).toEqual(5);
    expect(onChange).toHaveBeenCalledWith({'7': 5, '42': 'keep'});
});

test('follows a value replaced from outside without marking the inner store dirty', async() => {
    const {rerender, formInspector} = await renderLoaded({value: {'7': 3}});

    rerender(
        <ProductAttributes
            dataPath="/attributes"
            disabled={false}
            formInspector={formInspector}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            router={undefined}
            schemaPath="/attributes"
            showAllErrors={false}
            value={{'7': 5}}
            variant={false}
        />
    );

    expect(rendererProps.formInspector.getValueByPath('/attribute_7')).toEqual(5);
    expect(rendererProps.formInspector.formStore.dirty).toEqual(false);
});

test('finishes the row on the host form with its host paths', async() => {
    const onFinish = jest.fn();
    await renderLoaded({onFinish});

    await userEvent.click(screen.getByText('finish'));

    expect(onFinish).toHaveBeenCalledWith('/attributes/7', '/attributes');
});

test('passes the host errors of touched rows to the renderer, keyed by field name', async() => {
    const error = {'7': {keyword: 'maximum', parameters: {}}, '8': {keyword: 'required', parameters: {}}};
    const {formInspector} = await renderLoaded({error, value: {'7': 999}});

    expect(JSON.parse(screen.getByTestId('renderer-props').textContent).errors).toEqual({});

    formInspector.isFieldModified.mockImplementation((dataPath) => dataPath === '/attributes/7');
    act(() => {
        // $FlowFixMe
        formInspector.family.set('family-1x');
    });
    expect(await screen.findByTestId('renderer')).toBeInTheDocument();

    expect(JSON.parse(screen.getByTestId('renderer-props').textContent).errors).toEqual(
        {attribute_7: {keyword: 'maximum', parameters: {}}}
    );
});

test('passes every host error to the renderer when all errors are shown', async() => {
    const error = {'7': {keyword: 'maximum', parameters: {}}, '8': {keyword: 'required', parameters: {}}};
    await renderLoaded({error, showAllErrors: true});

    expect(JSON.parse(screen.getByTestId('renderer-props').textContent).errors).toEqual({
        attribute_7: {keyword: 'maximum', parameters: {}},
        attribute_8: {keyword: 'required', parameters: {}},
    });
});

test('reads the host errors from the observable array the form store builds for numeric ids', async() => {
    // jsonpointer.set(errors, '/attributes/7', ...) creates a sparse array, the store's @observable wraps it
    const hostErrors = [];
    hostErrors[7] = {keyword: 'maximum', parameters: {}};
    const {attributes: error} = observable({attributes: hostErrors});
    await renderLoaded({error, showAllErrors: true});

    expect(JSON.parse(screen.getByTestId('renderer-props').textContent).errors).toEqual({
        attribute_7: {keyword: 'maximum', parameters: {}},
    });
});

test('drops the empty rows from the filter callback once hide empty is toggled', async() => {
    await renderLoaded({value: {'7': 3, '8': null}});

    const filled = {name: 'attribute_7', schema: {label: 'Weight', type: 'number'}};
    const empty = {name: 'attribute_8', schema: {label: 'Colour', type: 'text_line'}};
    expect(rendererProps.filterItem(filled)).toEqual(true);
    expect(rendererProps.filterItem(empty)).toEqual(true);

    await userEvent.click(screen.getByRole('checkbox'));

    expect(rendererProps.filterItem(filled)).toEqual(true);
    expect(rendererProps.filterItem(empty)).toEqual(false);
});

test('keeps only the rows whose label contains the typed filter', async() => {
    await renderLoaded();

    await userEvent.type(screen.getByPlaceholderText('sulu_product.filter_attributes'), 'eig');

    expect(rendererProps.filterItem({name: 'attribute_7', schema: {label: 'Weight', type: 'number'}})).toEqual(true);
    expect(rendererProps.filterItem({name: 'attribute_8', schema: {label: 'Colour', type: 'text_line'}}))
        .toEqual(false);
});

test('reports an empty list and an empty object as empty', async() => {
    await renderLoaded({value: {'7': [], '8': {}}});

    await userEvent.click(screen.getByRole('checkbox'));

    expect(rendererProps.filterItem({name: 'attribute_7', schema: {label: 'Weight', type: 'number'}})).toEqual(false);
    expect(rendererProps.filterItem({name: 'attribute_8', schema: {label: 'Colour', type: 'text_line'}}))
        .toEqual(false);
});
