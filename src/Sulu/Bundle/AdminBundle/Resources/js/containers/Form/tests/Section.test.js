// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import ResourceStore from '../../../stores/ResourceStore';
import Field from '../Field';
import Section from '../Section';
import FormInspector from '../FormInspector';
import conditionDataProviderRegistry from '../registries/conditionDataProviderRegistry';
import fieldRegistry from '../registries/fieldRegistry';
import ResourceFormStore from '../stores/ResourceFormStore';

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions?.locale;
}));

jest.mock('../FormInspector', () => jest.fn(function(resourceFormStore) {
    this.locale = resourceFormStore.locale;
}));

jest.mock('../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('../registries/fieldRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn(),
}));

function createFormInspector() {
    return new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
}

function renderSection(props: Object = {}) {
    const formInspector = props.formInspector || createFormInspector();
    const data = props.data || {};

    return render(
        <Section
            data={data}
            formInspector={formInspector}
            name="section"
            schema={{label: 'Section', type: 'section', ...props.schema}}
        >
            <Field
                data={data}
                dataPath=""
                formInspector={formInspector}
                name="test"
                onChange={jest.fn()}
                onFinish={jest.fn()}
                onSuccess={jest.fn()}
                router={undefined}
                schema={{label: 'label1', type: 'text'}}
                schemaPath=""
            />
        </Section>
    );
}

beforeEach(() => {
    jest.clearAllMocks();
    conditionDataProviderRegistry.clear();
    fieldRegistry.get.mockReturnValue(function Text() {
        return <input data-testid="field-type" type="text" />;
    });
    fieldRegistry.getOptions.mockReturnValue(undefined);
});

test('Render section with children', () => {
    renderSection();

    expect(screen.getByText('Section')).toBeInTheDocument();
    expect(screen.getByText('label1')).toBeInTheDocument();
    expect(screen.getByTestId('field-type')).toBeInTheDocument();
});

test('Do not render anything if visibleCondition evaluates to false', () => {
    const data = observable({title: 'Test'});

    renderSection({
        data,
        schema: {
            label: 'Text',
            type: 'text_line',
            visibleCondition: 'title != "Test"',
        },
    });

    expect(screen.queryByText('Text')).not.toBeInTheDocument();
    expect(screen.queryByTestId('field-type')).not.toBeInTheDocument();

    act(() => {
        data.title = 'Changed title!';
    });

    expect(screen.getByText('Text')).toBeInTheDocument();
    expect(screen.getByTestId('field-type')).toBeInTheDocument();
});

test('Render the section if visibleCondition with conditionDataProvider evaluates to true', () => {
    conditionDataProviderRegistry.add((data) => ({__test: data.test}));

    renderSection({
        data: {test: 'Test'},
        schema: {
            label: 'Text',
            type: 'text_line',
            visibleCondition: '__test == "Test"',
        },
    });

    expect(screen.getByText('Text')).toBeInTheDocument();
    expect(screen.getByTestId('field-type')).toBeInTheDocument();
});
