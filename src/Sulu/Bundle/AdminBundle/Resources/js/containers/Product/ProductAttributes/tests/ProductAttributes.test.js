// @flow
import React from 'react';
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
        this.removeFieldValidator = jest.fn();
        this.addFieldValidator = jest.fn(() => this.removeFieldValidator);
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
            hideEmpty: props.hideEmpty,
            schema: props.schema,
            showAllErrors: props.showAllErrors,
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
    // empty values stay out of the inner data, so the JSON schema's required check catches them
    expect(JSON.parse(screen.getByTestId('renderer-props').textContent)).toEqual({
        data: {attribute_7: 3},
        disabled: false,
        hideEmpty: false,
        schema: SCHEMA,
        showAllErrors: false,
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
        this.addFieldValidator = jest.fn(() => jest.fn());
    });

    renderComponent({variant: true});

    expect(metadataStore.getSchema)
        .toHaveBeenCalledWith('product_attributes', undefined, {product: 'product-1', variant: true});
});

test('renders a hint and requests nothing without a family', () => {
    const FormInspectorMock = require('../../../Form/FormInspector');
    // $FlowFixMe
    FormInspectorMock.mockImplementationOnce(function() {
        this.options = {};
        this.getValueByPath = jest.fn(() => undefined);
        this.addFieldValidator = jest.fn(() => jest.fn());
    });

    renderComponent();

    expect(metadataStore.getSchema).not.toHaveBeenCalled();
    expect(screen.getByText('sulu_product.select_product_family_for_attributes')).toBeInTheDocument();
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
            showAllErrors={false}
            value={{'7': 5}}
            variant={false}
        />
    );

    expect(rendererProps.formInspector.getValueByPath('/attribute_7')).toEqual(5);
    expect(rendererProps.formInspector.formStore.dirty).toEqual(false);
});

test('validates the inner store when a row finishes and finishes the host field', async() => {
    const onFinish = jest.fn();
    await renderLoaded({onFinish, value: {'7': 999}});

    await userEvent.click(screen.getByText('finish'));

    expect(rendererProps.formInspector.errors).toEqual(
        {attribute_7: {keyword: 'maximum', parameters: {comparison: '<=', limit: 10}}}
    );
    expect(rendererProps.formInspector.isFieldModified('/attribute_7')).toEqual(true);
    expect(onFinish).toHaveBeenCalledTimes(1);
});

test('registers a validator on the host form that validates the inner store', async() => {
    const {formInspector} = await renderLoaded({value: {'7': 999}});

    expect(formInspector.addFieldValidator).toHaveBeenCalledWith('/attributes', expect.any(Function));
    const validator = formInspector.addFieldValidator.mock.calls[0][1];

    expect(validator()).toEqual({attribute_7: {keyword: 'maximum', parameters: {comparison: '<=', limit: 10}}});
    expect(rendererProps.formInspector.errors).toEqual(
        {attribute_7: {keyword: 'maximum', parameters: {comparison: '<=', limit: 10}}}
    );

    await userEvent.click(screen.getByText('change'));
    expect(validator()).toEqual(undefined);
});

test('the host validator reports a missing required value', async() => {
    const {formInspector} = await renderLoaded({value: {'7': null}});
    const validator = formInspector.addFieldValidator.mock.calls[0][1];

    expect(validator()).toEqual({attribute_7: {keyword: 'required', parameters: {missingProperty: 'attribute_7'}}});
});

test('the host validator passes while the metadata is still loading', () => {
    metadataStore.getSchema.mockReturnValue(deferred().promise);
    const {formInspector} = renderComponent();
    const validator = formInspector.addFieldValidator.mock.calls[0][1];

    expect(validator()).toEqual(undefined);
});

test('removes the host validator on unmount', async() => {
    const {formInspector, unmount} = await renderLoaded();

    unmount();

    expect(formInspector.removeFieldValidator).toHaveBeenCalledTimes(1);
});

test('toggles hide empty', async() => {
    await renderLoaded();

    await userEvent.click(screen.getByRole('checkbox'));

    // eslint-disable-next-line jest-dom/prefer-to-have-text-content
    expect(screen.getByTestId('renderer-props').textContent).toContain('"hideEmpty":true');
});

test('passes showAllErrors to the renderer', async() => {
    await renderLoaded({showAllErrors: true});

    // eslint-disable-next-line jest-dom/prefer-to-have-text-content
    expect(screen.getByTestId('renderer-props').textContent).toContain('"showAllErrors":true');
});
