// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {fieldRegistry, metadataStore} from 'sulu-admin-bundle/containers/Form';
import ShadowRoute from '../../fields/ShadowRoute';

const mockReact = require('react');

jest.mock('sulu-admin-bundle/containers/Form', () => ({
    fieldRegistry: {
        get: jest.fn(),
        getOptions: jest.fn(),
    },
    metadataStore: {
        getSchema: jest.fn(),
        getSchemaTypes: jest.fn(),
    },
}));

const routeSchema = {
    title: {type: 'text_line'},
    general: {
        type: 'section',
        items: {
            url: {type: 'route', options: {mode: {value: 'leaf'}}},
        },
    },
};

function createFormInspector(template: ?string, metadataOptions: Object = {templateType: 'pages'}): any {
    return {
        metadataOptions,
        getValueByPath: jest.fn((path) => path === '/template' ? template : undefined),
    };
}

beforeEach(() => {
    fieldRegistry.get.mockReset();
    fieldRegistry.getOptions.mockReset();
    metadataStore.getSchema.mockReset();
    metadataStore.getSchemaTypes.mockReset();

    fieldRegistry.get.mockReturnValue(
        (props) => mockReact.createElement('input', {
            'data-mode': props.schemaOptions.mode?.value,
            'data-generation-url': props.fieldTypeOptions.generationUrl,
            'readOnly': true,
            'value': props.value,
        })
    );
    fieldRegistry.getOptions.mockReturnValue({generationUrl: '/generate'});
});

test('Render the route field of the template of the content', async() => {
    metadataStore.getSchema.mockReturnValue(Promise.resolve(routeSchema));

    render(
        <ShadowRoute
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector('default')}
            value="/url"
        />
    );

    const input = await screen.findByRole('textbox');

    expect(metadataStore.getSchema).toHaveBeenCalledWith('pages', 'default');
    expect(fieldRegistry.get).toHaveBeenCalledWith('route');
    expect(input).toHaveValue('/url');
    expect(input).toHaveAttribute('data-mode', 'leaf');
    expect(input).toHaveAttribute('data-generation-url', '/generate');
});

test('Render the route field of the default template if the content has no template yet', async() => {
    metadataStore.getSchemaTypes.mockReturnValue(Promise.resolve({defaultType: 'overview', types: {}}));
    metadataStore.getSchema.mockReturnValue(Promise.resolve({url: {type: 'page_tree_route'}}));

    render(
        <ShadowRoute
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector(undefined)}
            value="/url"
        />
    );

    await waitFor(() => expect(fieldRegistry.get).toHaveBeenCalledWith('page_tree_route'));

    expect(metadataStore.getSchema).toHaveBeenCalledWith('pages', 'overview');
});

test('Render nothing if the template has no route field', async() => {
    metadataStore.getSchema.mockReturnValue(Promise.resolve({title: {type: 'text_line'}}));

    const {container} = render(
        <ShadowRoute
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector('default')}
        />
    );

    await waitFor(() => expect(metadataStore.getSchema).toHaveBeenCalled());

    expect(container).toBeEmptyDOMElement();
});

test('Render nothing without a template type', () => {
    const {container} = render(
        <ShadowRoute
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector('default', {})}
        />
    );

    expect(metadataStore.getSchema).not.toHaveBeenCalled();
    expect(container).toBeEmptyDOMElement();
});

test('Render nothing if the content has neither a template nor a default template', async() => {
    metadataStore.getSchemaTypes.mockReturnValue(Promise.resolve({defaultType: undefined, types: {}}));

    const {container} = render(
        <ShadowRoute
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector(undefined)}
        />
    );

    await waitFor(() => expect(metadataStore.getSchemaTypes).toHaveBeenCalled());

    expect(metadataStore.getSchema).not.toHaveBeenCalled();
    expect(container).toBeEmptyDOMElement();
});
