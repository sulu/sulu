// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FormInspector from '../../../Form/FormInspector';
import ProductAttributesRenderer from '../ProductAttributesRenderer';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

// The inner form inspector of the container: value, errors and modified state live in its store.
jest.mock('../../../Form/FormInspector', () => jest.fn(function() {
    this.errors = {};
    this.getValueByPath = jest.fn((dataPath) => this.data[dataPath.substring(1)]);
    this.isFieldModified = jest.fn(() => false);
}));

// The container Field is the real form field renderer; stand in with an input that shows what it got.
jest.mock('../../../Form/Field', () => function Field(props) {
    const React = require('react');

    return React.createElement('input', {
        'data-disabled-condition': props.schema.disabledCondition,
        'data-error': props.error ? props.error.keyword : undefined,
        'data-label': props.schema.label,
        'data-name': props.name,
        'data-path': props.dataPath,
        'data-schema-path': props.schemaPath,
        'data-type': props.schema.type,
        disabled: props.schema.disabledCondition === 'true',
        onBlur: () => props.onFinish(props.dataPath, props.schemaPath),
        onChange: (event) => props.onChange(props.name, event.target.value),
        value: props.value === undefined || props.value === null ? '' : props.value,
    });
});

const SCHEMA = {
    attribute_group_1: {
        items: {
            attribute_7: {label: 'Weight (kg)', options: {}, required: true, type: 'number'},
            attribute_8: {label: 'Colour', options: {}, required: false, type: 'text_line'},
        },
        label: 'Dimensions',
        type: 'section',
    },
    attribute_group_2: {
        items: {
            attribute_9: {label: 'Voltage (V)', options: {}, required: false, type: 'number'},
        },
        label: 'Electrical',
        type: 'section',
    },
};

const DATA = {attribute_7: 12, attribute_9: '', attribute_42: 'stale'};

// $FlowFixMe
const formInspector: FormInspector = new FormInspector();

function renderComponent(props: Object = {}) {
    // $FlowFixMe
    formInspector.data = props.data || DATA;
    // $FlowFixMe
    formInspector.errors = props.errors || {};

    return render(
        <ProductAttributesRenderer
            data={DATA}
            disabled={false}
            formInspector={formInspector}
            hideEmpty={false}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            router={undefined}
            schema={SCHEMA}
            showAllErrors={false}
            {...props}
        />
    );
}

test('renders one card per section with the section label', () => {
    renderComponent();

    expect(screen.getByText('Dimensions')).toBeInTheDocument();
    expect(screen.getByText('Electrical')).toBeInTheDocument();
});

test('renders one row per field with the label, the required marker and the field type', () => {
    renderComponent();

    expect(screen.getByText('Weight (kg) *')).toBeInTheDocument();
    expect(screen.getByText('Colour')).toBeInTheDocument();

    const inputs = screen.getAllByRole('textbox');
    expect(inputs).toHaveLength(3);
    expect(inputs[0]).toHaveAttribute('data-name', 'attribute_7');
    expect(inputs[0]).toHaveAttribute('data-type', 'number');
    expect(inputs[0]).toHaveAttribute('data-path', '/attribute_7');
    expect(inputs[0]).toHaveAttribute('data-schema-path', '/attribute_group_1/items/attribute_7');
    expect(inputs[0]).toHaveValue('12');
    // the label is drawn by the row, not by Form.Field
    expect(inputs[0]).not.toHaveAttribute('data-label');
});

test('emits the field name and finishes the field with its paths', async() => {
    const onChange = jest.fn();
    const onFinish = jest.fn();
    renderComponent({onChange, onFinish});

    const input = screen.getAllByRole('textbox')[1];
    await userEvent.type(input, 'r');
    expect(onChange).toHaveBeenLastCalledWith('attribute_8', 'r');

    await userEvent.tab();
    expect(onFinish).toHaveBeenCalledWith('/attribute_8', '/attribute_group_1/items/attribute_8');
});

const ERRORS = {
    attribute_7: {keyword: 'maximum', parameters: {}},
    attribute_9: {keyword: 'required', parameters: {}},
};

test('shows the store errors on the rows when all errors are shown', () => {
    renderComponent({errors: ERRORS, showAllErrors: true});

    expect(screen.getAllByRole('textbox')[0]).toHaveAttribute('data-error', 'maximum');
    expect(screen.getAllByRole('textbox')[1]).not.toHaveAttribute('data-error');
    expect(screen.getAllByRole('textbox')[2]).toHaveAttribute('data-error', 'required');
});

test('hides the store errors until a row was modified', () => {
    formInspector.isFieldModified.mockImplementation((dataPath) => dataPath === '/attribute_7');
    renderComponent({errors: ERRORS, showAllErrors: false});

    expect(screen.getAllByRole('textbox')[0]).toHaveAttribute('data-error', 'maximum');
    expect(screen.getAllByRole('textbox')[2]).not.toHaveAttribute('data-error');
    formInspector.isFieldModified.mockImplementation(() => false);
});

test('hides empty rows and empty groups when hideEmpty is set', () => {
    renderComponent({hideEmpty: true});

    expect(screen.getByText('Dimensions')).toBeInTheDocument();
    expect(screen.getByText('Weight (kg) *')).toBeInTheDocument();
    expect(screen.queryByText('Colour')).not.toBeInTheDocument();
    expect(screen.queryByText('Electrical')).not.toBeInTheDocument();
});

test('disables every field when disabled', () => {
    renderComponent({disabled: true});

    screen.getAllByRole('textbox').forEach((input) => {
        expect(input).toBeDisabled();
    });
});

test('renders the toolbar and the collapse all toggle', () => {
    renderComponent({toolbar: <span>toolbar-content</span>});

    expect(screen.getByText('toolbar-content')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.collapse_all')).toBeInTheDocument();
});

test('passes the schema disabledCondition through unchanged when not disabled', () => {
    const schema = {
        attribute_group_1: {
            items: {
                attribute_7: {
                    disabledCondition: 'type == \'x\'',
                    label: 'Weight (kg)',
                    options: {},
                    required: true,
                    type: 'number',
                },
            },
            label: 'Dimensions',
            type: 'section',
        },
    };

    renderComponent({disabled: false, schema});

    expect(screen.getByRole('textbox')).toHaveAttribute('data-disabled-condition', 'type == \'x\'');
});

test('forces the disabledCondition to "true" when disabled, overriding the schema value', () => {
    const schema = {
        attribute_group_1: {
            items: {
                attribute_7: {
                    disabledCondition: 'type == \'x\'',
                    label: 'Weight (kg)',
                    options: {},
                    required: true,
                    type: 'number',
                },
            },
            label: 'Dimensions',
            type: 'section',
        },
    };

    renderComponent({disabled: true, schema});

    expect(screen.getByRole('textbox')).toHaveAttribute('data-disabled-condition', 'true');
});

test('collapsing a card hides its rows', async() => {
    renderComponent();

    // $FlowFixMe
    const card = screen.getByText('Dimensions').closest('section');
    await userEvent.click(within(card).getByLabelText('su-collapse-vertical'));

    expect(screen.queryByText('Colour')).not.toBeInTheDocument();
    expect(screen.getByText('Voltage (V)')).toBeInTheDocument();
});
