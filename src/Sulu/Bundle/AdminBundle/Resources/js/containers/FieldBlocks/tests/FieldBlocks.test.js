// @flow
/* eslint-disable react/jsx-no-bind */
import React from 'react';
import {observable} from 'mobx';
import {render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router from '../../../services/Router';
import fieldTypeDefaultProps from '../../../utils/TestHelper/fieldTypeDefaultProps';
import FieldBlocks from '../FieldBlocks';
import FormInspector from '../../Form/FormInspector';
import {memoryFormStoreFactory} from '../../Form';
import metadataStore from '../../Form/stores/metadataStore';
import ResourceFormStore from '../../Form/stores/ResourceFormStore';
import ResourceStore from '../../../stores/ResourceStore';
import blockPreviewTransformerRegistry from '../registries/blockPreviewTransformerRegistry';
import fieldRegistry from '../../Form/registries/fieldRegistry';
import SingleSelect from '../../Form/fields/SingleSelect';
import conditionDataProviderRegistry from '../../Form/registries/conditionDataProviderRegistry';

jest.mock('../../../components/BlockCollection', () => {
    const React = require('react');
    const {toJS} = require('mobx');

    return jest.fn((props) => {
        const [expandedBlocks, setExpandedBlocks] = React.useState({});

        const expandBlock = (index) => {
            setExpandedBlocks({...expandedBlocks, [index]: true});
        };

        return (
            <div
                data-add-button-text={props.addButtonText}
                data-collapsable={String(props.collapsable ?? true)}
                data-disabled={String(props.disabled)}
                data-max-occurs={String(props.maxOccurs)}
                data-min-occurs={String(props.minOccurs)}
                data-movable={String(props.movable ?? true)}
                data-paste-button-text={props.pasteButtonText}
                data-testid="block-collection"
                data-types={JSON.stringify(props.types)}
                data-value={JSON.stringify(toJS(props.value))}
            >
                {props.value.map((block, index) => {
                    const expanded = !!expandedBlocks[index];

                    return (
                        <div data-testid={'block-' + index} key={index}>
                            <span data-testid={'block-type-' + index}>{block.type}</span>
                            <span data-testid={'block-icons-' + index}>
                                {(props.icons?.[index] || []).map((icon) => (
                                    <span aria-label={icon} key={icon} />
                                ))}
                            </span>
                            <button onClick={() => expandBlock(index)} type="button">
                                {'expand-' + index}
                            </button>
                            {props.onSettingsClick &&
                                <button onClick={() => props.onSettingsClick(index)} type="button">
                                    {'settings-' + index}
                                </button>
                            }
                            <div data-testid={'block-content-' + index}>
                                {props.renderBlockContent(block, block.type, index, expanded)}
                            </div>
                        </div>
                    );
                })}
                <button onClick={() => props.onSortEnd(0, 2)} type="button">
                    sort-end
                </button>
                <button onClick={() => props.onChange([{type: 'other'}])} type="button">
                    change-to-other
                </button>
            </div>
        );
    });
});

jest.mock('../FieldRenderer', () => {
    const React = require('react');
    const {toJS} = require('mobx');

    return jest.fn((props) => {
        React.useEffect(() => {
            Object.keys(props.schema).forEach((schemaKey) => {
                const schemaEntry = props.schema[schemaKey];
                const defaultValue = schemaEntry.options?.default_value?.value;

                if (
                    schemaEntry.type === 'single_select'
                    && defaultValue !== undefined
                    && props.value[schemaKey] === undefined
                ) {
                    props.onChange(props.index, schemaKey, defaultValue, {isDefaultValue: true});
                }
            });
        }, []);

        return (
            <div
                data-data={JSON.stringify(props.data)}
                data-has-router={props.router ? 'true' : 'false'}
                data-index={String(props.index)}
                data-schema-path={props.schemaPath}
                data-testid={'field-renderer-' + props.index}
                data-value={JSON.stringify(toJS(props.value))}
            >
                {Object.keys(props.schema).map((schemaKey) => {
                    const error = props.errors?.[schemaKey];

                    return (
                        <input
                            aria-label={'field-' + props.index + '-' + schemaKey}
                            className={error?.keyword}
                            defaultValue={props.value[schemaKey] || ''}
                            key={schemaKey}
                            type="text"
                        />
                    );
                })}
                <button onClick={() => props.onFieldFinish()} type="button">
                    {'finish-field-' + props.index}
                </button>
                <button onClick={() => props.onChange(props.index, 'options/test1', 'value1')} type="button">
                    {'change-nested-1-' + props.index}
                </button>
                <button onClick={() => props.onChange(props.index, 'options/test2/test3', 'value2')} type="button">
                    {'change-nested-2-' + props.index}
                </button>
                <button
                    onClick={() => props.onChange(props.index, 'alignment', 'left', {isDefaultValue: true})}
                    type="button"
                >
                    {'change-with-context-' + props.index}
                </button>
            </div>
        );
    });
});

jest.mock('../../FormOverlay', () => {
    const React = require('react');

    return jest.fn((props) => {
        if (!props.open) {
            return null;
        }

        return (
            <div data-confirm-disabled={String(props.confirmDisabled)} data-testid="form-overlay">
                <button onClick={props.onClose} type="button">close-settings</button>
                <button onClick={() => props.formStore.change('setting', true)} type="button">set-setting-true</button>
                <button onClick={() => props.formStore.change('setting', false)} type="button">
                    set-setting-false
                </button>
                <button
                    onClick={() => {
                        if (!props.formStore.validate()) {
                            return;
                        }

                        props.onConfirm();
                    }}
                    type="button"
                >
                    {props.confirmText}
                </button>
            </div>
        );
    });
});

jest.mock('../../../services/Router/Router', () => jest.fn());
jest.mock('../../Form/FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
    this.getSchemaEntryByPath = jest.fn();
}));
jest.mock('../../Form/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
}));
jest.mock('../../Form/stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('../../Form/registries/fieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'checkbox':
                return function Checkbox({value}) {
                    return <input type="checkbox" value={value} />;
                };
            case 'text_line':
                return function TextLine({error, value}) {
                    return <input className={error && error.keyword} defaultValue={value} type="text" />;
                };
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator');

jest.mock('../registries/blockPreviewTransformerRegistry', () => ({
    has: jest.fn(),
    get: jest.fn(),
    blockPreviewTransformerKeysByPriority: [],
}));

function createFormInspector() {
    return new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
}

function renderFieldBlocks(props: Object = {}) {
    return render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            {...props}
        />
    );
}

function getDefaultTypes(form: Object = {}) {
    return {
        default: {
            title: 'Default',
            form,
        },
    };
}

function mockTransformer(key: string, label: string = key) {
    blockPreviewTransformerRegistry.has.mockImplementation((transformerKey) => transformerKey === key);
    blockPreviewTransformerRegistry.get.mockImplementation((transformerKey) => {
        if (transformerKey === key) {
            return {
                transform: (value) => <p>{label === key ? value : label}</p>,
            };
        }
    });
}

beforeEach(() => {
    blockPreviewTransformerRegistry.has.mockClear();
    blockPreviewTransformerRegistry.get.mockClear();
    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [];
    conditionDataProviderRegistry.clear();
});

test('Render collapsed blocks with block previews', async() => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        text1: {label: 'Text 1', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
        text2: {label: 'Text 2', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
        something: {label: 'Something', tags: [{name: 'sulu.block_preview'}], type: 'text_area'},
        nothing: {label: 'Nothing', type: 'text_line'},
    });
    formInspector.getSchemaEntryByPath.mockReturnValue({types});
    const schemaPromise = Promise.resolve({
        setting: {
            tags: [{attributes: {icon: 'su-eye'}, name: 'sulu.block_setting_icon'}],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    mockTransformer('text_line');

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types,
        value: [
            {text1: 'Test 1', text2: undefined, something: 'Test 3', type: 'default', settings: {setting: true}},
            {text1: 'Test 4', text2: undefined, something: 'Test 6', type: 'default', settings: {}},
        ],
    });

    await Promise.all([schemaPromise, jsonSchemaPromise]);

    expect(screen.getByText('Test 1')).toBeInTheDocument();
    expect(screen.getByText('Test 4')).toBeInTheDocument();
    expect(screen.queryByText('Test 3')).not.toBeInTheDocument();
    expect(screen.getByLabelText('su-eye')).toBeInTheDocument();
});

test('Render collapsed blocks with block previews and sections', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        section1: {
            label: 'Section',
            type: 'section',
            items: {
                text1: {label: 'Text 1', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
                text2: {label: 'Text 2', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
                something: {label: 'Something', tags: [{name: 'sulu.block_preview'}], type: 'text_area'},
            },
        },
    });
    formInspector.getSchemaEntryByPath.mockReturnValue({types});
    blockPreviewTransformerRegistry.has.mockReturnValue(true);
    blockPreviewTransformerRegistry.get.mockReturnValue({transform: (value) => <p>{value}</p>});

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        types,
        value: [
            {text1: 'Test 1', something: 'Test 2', type: 'default'},
            {text2: 'Test 3', something: 'Test 4', type: 'default'},
        ],
    });

    expect(screen.getByText('Test 1')).toBeInTheDocument();
    expect(screen.getByText('Test 2')).toBeInTheDocument();
    expect(screen.getByText('Test 3')).toBeInTheDocument();
    expect(screen.getByText('Test 4')).toBeInTheDocument();
});

test('Render collapsed blocks with block previews without tags and with sections', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        section1: {
            label: 'Section',
            type: 'section',
            items: {
                nothing: {label: 'Nothing', type: 'phone'},
                text1: {label: 'Text 1', type: 'text_line'},
                text2: {label: 'Text 2', type: 'media_selection'},
                something: {label: 'Text 3', type: 'text_editor'},
            },
        },
    });
    formInspector.getSchemaEntryByPath.mockReturnValue({types});
    blockPreviewTransformerRegistry.has.mockReturnValue(true);
    blockPreviewTransformerRegistry.get.mockImplementation((key) => ({transform: () => <p>{key}</p>}));
    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [
        'media_selection',
        'text_line',
        'text_editor',
    ];

    renderFieldBlocks({
        defaultType: 'default',
        formInspector,
        types,
        value: [{nothing: 'phone', text1: 'Test 1', text2: 'Test 2', something: 'Test 3', type: 'default'}],
    });

    expect(screen.getByText('media_selection')).toBeInTheDocument();
    expect(screen.getByText('text_line')).toBeInTheDocument();
    expect(screen.getByText('text_editor')).toBeInTheDocument();
    expect(screen.queryByText('phone')).not.toBeInTheDocument();
});

test('Render collapsed blocks with block previews without tags', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        nothing: {label: 'Nothing', type: 'phone'},
        text1: {label: 'Text 1', type: 'text_line'},
        text2: {label: 'Text 2', type: 'media_selection'},
        something: {label: 'Text 3', type: 'text_editor'},
    });
    formInspector.getSchemaEntryByPath.mockReturnValue({types});
    blockPreviewTransformerRegistry.has.mockReturnValue(true);
    blockPreviewTransformerRegistry.get.mockImplementation((key) => ({transform: () => <p>{key}</p>}));
    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [
        'media_selection',
        'text_line',
        'text_editor',
    ];

    renderFieldBlocks({
        defaultType: 'default',
        formInspector,
        types,
        value: [{nothing: 'phone', text1: 'Test 1', text2: 'Test 2', something: 'Test 3', type: 'default'}],
    });

    expect(screen.getByText('media_selection')).toBeInTheDocument();
    expect(screen.getByText('text_line')).toBeInTheDocument();
    expect(screen.getByText('text_editor')).toBeInTheDocument();
});

test('Render collapsed blocks with prioritized block previews', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        text1: {label: 'Text 1', tags: [{name: 'sulu.block_preview', priority: -100}], type: 'text_line'},
        text2: {label: 'Text 2', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
        something: {label: 'Text 3', tags: [{name: 'sulu.block_preview', priority: 100}], type: 'text_line'},
    });
    formInspector.getSchemaEntryByPath.mockReturnValue({types});
    mockTransformer('text_line');

    renderFieldBlocks({
        defaultType: 'default',
        formInspector,
        types,
        value: [{text1: 'Test 1', text2: 'Test 2', something: 'Test 3', type: 'default'}],
    });

    expect(within(screen.getByTestId('block-content-0')).getAllByText(/Test/).map((node) => node.textContent))
        .toEqual(['Test 3', 'Test 2', 'Test 1']);
});

test('Render block with schema', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        text1: {label: 'Text 1', type: 'text_line'},
        text2: {label: 'Text 2', type: 'text_line'},
    });
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        types,
        value: [
            {text1: 'Test 1', text2: 'Test 2', type: 'default'},
            {text1: 'Test 3', text2: 'Test 4', type: 'default'},
        ],
    });

    await user.click(screen.getByRole('button', {name: 'expand-0'}));
    await user.click(screen.getByRole('button', {name: 'expand-1'}));

    expect(screen.getByLabelText('field-0-text1')).toHaveValue('Test 1');
    expect(screen.getByLabelText('field-1-text2')).toHaveValue('Test 4');
});

test('Call not onChange on componentDidUpdate when new types are the same', () => {
    const formInspector = createFormInspector();
    const changeSpy = jest.fn();
    const types = getDefaultTypes({
        text1: {label: 'Text 1', type: 'text_line'},
        text2: {label: 'Text 2', type: 'text_line'},
    });

    const {rerender} = renderFieldBlocks({
        defaultType: 'default',
        formInspector,
        onChange: changeSpy,
        types,
        value: [
            {text1: 'Test 1', text2: 'Test 2', type: 'default'},
            {text1: 'Test 3', text2: 'Test 4', type: 'default'},
        ],
    });

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={getDefaultTypes({
                text1: {label: 'Text 1 a', type: 'text_line'},
                text2: {label: 'Text 2 b', type: 'text_line'},
            })}
            value={[
                {text1: 'Test 1 a', text2: 'Test 2 b', type: 'default'},
                {text1: 'Test 3 a', text2: 'Test 4 c', type: 'default'},
            ]}
        />
    );

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Call onChange on componentDidUpdate when type not longer exist', () => {
    const formInspector = createFormInspector();
    const changeSpy = jest.fn();
    const types = getDefaultTypes({
        text1: {label: 'Text 1', type: 'text_line'},
        text2: {label: 'Text 2', type: 'text_line'},
    });

    const {rerender} = renderFieldBlocks({
        defaultType: 'default',
        formInspector,
        onChange: changeSpy,
        types,
        value: [
            {text1: 'Test 1', text2: 'Test 2', type: 'default'},
            {text1: 'Test 3', text2: 'Test 4', type: 'default'},
        ],
    });

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="new"
            formInspector={formInspector}
            onChange={changeSpy}
            types={{
                new: {
                    title: 'Default',
                    form: {
                        text1: {label: 'Text 1', type: 'text_line'},
                        text2: {label: 'Text 2', type: 'text_line'},
                    },
                },
            }}
            value={[
                {text1: 'Test 1', text2: 'Test 2', type: 'not-exist'},
                {text1: 'Test 3', text2: 'Test 4', type: 'not-exist'},
            ]}
        />
    );

    expect(changeSpy).toHaveBeenCalledWith([
        {text1: 'Test 1', text2: 'Test 2', type: 'new'},
        {text1: 'Test 3', text2: 'Test 4', type: 'new'},
    ]);
});

test('Render block with schema and error when showing all errors', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});
    formInspector.isFieldModified.mockImplementation((dataPath) => {
        return dataPath === '/block/0/text' || dataPath === '/block/1/text';
    });

    renderFieldBlocks({
        dataPath: '/block',
        defaultType: 'editor',
        error: [
            undefined,
            {text: {keyword: 'minLength', parameters: {}}},
            {text: {keyword: 'minLength', parameters: {}}},
        ],
        formInspector,
        schemaPath: '/block',
        types,
        value: [{text: 'Test1', type: 'default'}, {text: 'T2', type: 'default'}, {text: 'T3', type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'expand-0'}));
    await user.click(screen.getByRole('button', {name: 'expand-1'}));
    await user.click(screen.getByRole('button', {name: 'expand-2'}));

    expect(screen.getByLabelText('field-1-text')).toHaveClass('minLength');
    expect(screen.getByLabelText('field-2-text')).toHaveClass('minLength');
});

test('Render block with schema and error on fields already being modified', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});

    renderFieldBlocks({
        defaultType: 'editor',
        error: [
            undefined,
            {text: {keyword: 'minLength', parameters: {}}},
            {text: {keyword: 'minLength', parameters: {}}},
        ],
        formInspector,
        showAllErrors: true,
        types,
        value: [{text: 'Test1', type: 'default'}, {text: 'T2', type: 'default'}, {text: 'T3', type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'expand-0'}));
    await user.click(screen.getByRole('button', {name: 'expand-1'}));
    await user.click(screen.getByRole('button', {name: 'expand-2'}));

    expect(screen.getByLabelText('field-1-text')).toHaveClass('minLength');
    expect(screen.getByLabelText('field-2-text')).toHaveClass('minLength');
});

test('Should correctly pass props to the BlockCollection', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});
    const value = [];

    renderFieldBlocks({
        defaultType: 'editor',
        disabled: true,
        formInspector,
        label: 'Test',
        maxOccurs: 2,
        minOccurs: 1,
        schemaOptions: {
            add_button_text: {name: 'add_button_text', title: 'custom-add-text'},
            paste_button_text: {name: 'paste_button_text', title: 'custom-paste-text'},
        },
        types,
        value,
    });

    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-add-button-text', 'custom-add-text');
    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-paste-button-text', 'custom-paste-text');
    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-collapsable', 'true');
    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-disabled', 'true');
    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-max-occurs', '2');
    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-min-occurs', '1');
    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-movable', 'true');
    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-types', JSON.stringify({default: 'Default'}));
});

test('Should pass collapsable and movable props to the BlockCollection', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line', visible: true}});

    renderFieldBlocks({
        defaultType: 'editor',
        disabled: true,
        formInspector,
        maxOccurs: 2,
        minOccurs: 1,
        schemaOptions: {
            movable: {name: 'movable', value: false},
            collapsable: {name: 'collapsable', value: false},
        },
        types,
        value: [],
    });

    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-collapsable', 'false');
    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-movable', 'false');
});

test('Should pass new value to the BlockCollection if value prop is updated', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});

    const {rerender} = renderFieldBlocks({
        defaultType: 'editor',
        disabled: true,
        formInspector,
        maxOccurs: 2,
        minOccurs: 1,
        types,
        value: [],
    });

    expect(screen.getByTestId('block-collection')).toHaveAttribute('data-value', JSON.stringify([]));

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={[{type: 'default', text: 'One'}]}
        />
    );
    expect(screen.getByTestId('block-collection')).toHaveAttribute(
        'data-value',
        JSON.stringify([{type: 'default', text: 'One'}])
    );

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={observable([{type: 'default', text: 'Two'}])}
        />
    );
    expect(screen.getByTestId('block-collection')).toHaveAttribute(
        'data-value',
        JSON.stringify([{type: 'default', text: 'Two'}])
    );
});

test('Should pass correct data and value and router to FieldRenderer', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const router = new Router();
    const types = getDefaultTypes({text: {type: 'text_line'}});
    const data = {title: 'Test'};
    const value = [{title: 'Test 1', type: 'default'}, {title: 'Test 2', type: 'default'}];

    renderFieldBlocks({data, dataPath: '', defaultType: 'editor', formInspector, router, schemaPath: '', types, value});

    await user.click(screen.getByRole('button', {name: 'expand-0'}));
    await user.click(screen.getByRole('button', {name: 'expand-1'}));

    expect(screen.getByTestId('field-renderer-0')).toHaveAttribute('data-data', JSON.stringify(data));
    expect(screen.getByTestId('field-renderer-0')).toHaveAttribute('data-value', JSON.stringify(value[0]));
    expect(screen.getByTestId('field-renderer-1')).toHaveAttribute('data-data', JSON.stringify(data));
    expect(screen.getByTestId('field-renderer-1')).toHaveAttribute('data-value', JSON.stringify(value[1]));
    expect(screen.getByTestId('field-renderer-0')).toHaveAttribute('data-has-router', 'true');
});

test('Should pass correct schemaPath and router to FieldRenderer', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const router = new Router();
    const types = getDefaultTypes({text: {type: 'text_line'}});

    renderFieldBlocks({
        dataPath: '',
        defaultType: 'editor',
        formInspector,
        router,
        schemaPath: '',
        types,
        value: [{type: 'default'}, {type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'expand-0'}));
    await user.click(screen.getByRole('button', {name: 'expand-1'}));

    expect(screen.getByTestId('field-renderer-0')).toHaveAttribute('data-schema-path', '/types/default/form');
    expect(screen.getByTestId('field-renderer-1')).toHaveAttribute('data-schema-path', '/types/default/form');
    expect(screen.getByTestId('field-renderer-0')).toHaveAttribute('data-has-router', 'true');
});

test('Should call onFinish when a field from the child renderer has finished editing', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});
    const finishSpy = jest.fn();

    renderFieldBlocks({
        dataPath: '',
        defaultType: 'editor',
        formInspector,
        onFinish: finishSpy,
        schemaPath: '',
        types,
        value: [{type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'expand-0'}));
    await user.click(screen.getByRole('button', {name: 'finish-field-0'}));

    expect(finishSpy).toHaveBeenCalledWith();
});

test ('Should set nested properties in handleBlockChange and call onChange with new values', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});
    const changeSpy = jest.fn();

    renderFieldBlocks({
        dataPath: '',
        defaultType: 'editor',
        formInspector,
        onChange: changeSpy,
        schemaPath: '',
        types,
        value: [{type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'expand-0'}));
    await user.click(screen.getByRole('button', {name: 'change-nested-1-0'}));

    expect(changeSpy).toHaveBeenCalledWith([{type: 'default', options: {test1: 'value1'}}], undefined);

    await user.click(screen.getByRole('button', {name: 'change-nested-2-0'}));
    expect(changeSpy).toHaveBeenCalledWith(
        [{type: 'default', options: {test1: 'value1', test2: {test3: 'value2'}}}],
        undefined
    );
});

test('Should pass context through handleBlockChange to onChange', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});
    const changeSpy = jest.fn();

    renderFieldBlocks({
        dataPath: '',
        defaultType: 'editor',
        formInspector,
        onChange: changeSpy,
        schemaPath: '',
        types,
        value: [{type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'expand-0'}));
    await user.click(screen.getByRole('button', {name: 'change-with-context-0'}));

    expect(changeSpy).toHaveBeenCalledWith(
        [{type: 'default', alignment: 'left'}],
        {isDefaultValue: true}
    );
});

test('Should call onFinish when the order of the blocks has changed', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const finishSpy = jest.fn();

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        onFinish: finishSpy,
        types: getDefaultTypes({text: {label: 'Text', type: 'text_line'}}),
        value: [{type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'sort-end'}));

    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should open and close block settings overlay close button is clicked', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types,
        value: [{type: 'default'}],
    });

    expect(screen.queryByTestId('form-overlay')).not.toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'settings-0'}));
    expect(screen.getByTestId('form-overlay')).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'close-settings'}));
    expect(screen.queryByTestId('form-overlay')).not.toBeInTheDocument();
});

test('Should open and close block settings overlay when confirm button is clicked with changed data', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});
    const schemaPromise = Promise.resolve({setting: {tags: [], type: 'checkbox'}});
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        onChange: changeSpy,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types,
        value: [{type: 'default', settings: {setting: false}}, {type: 'default', settings: {setting: false}}],
    });

    expect(metadataStore.getSchema).toHaveBeenCalledWith('page_block_settings', undefined, undefined);
    expect(metadataStore.getJsonSchema).toHaveBeenCalledWith('page_block_settings', undefined, undefined);

    await user.click(screen.getByRole('button', {name: 'settings-1'}));
    await Promise.all([schemaPromise, jsonSchemaPromise]);
    expect(await screen.findByTestId('form-overlay')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'set-setting-true'}));
    expect(changeSpy).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.apply'}));
    expect(screen.queryByTestId('form-overlay')).not.toBeInTheDocument();
    expect(changeSpy).toHaveBeenCalledWith([
        {type: 'default', settings: {setting: false}},
        {type: 'default', settings: {setting: true}},
    ]);
});

test('Should destroy create new formstore when block settings overlay is opened for another block', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line'}});

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types,
        value: [{type: 'default'}, {type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'settings-0'}));
    const firstOverlay = screen.getByTestId('form-overlay');
    await user.click(screen.getByRole('button', {name: 'close-settings'}));
    await user.click(screen.getByRole('button', {name: 'settings-1'}));

    expect(screen.getByTestId('form-overlay')).not.toBe(firstOverlay);
});

test('Should not close block settings overlay when confirm button is clicked with invalid data', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({text: {label: 'Text', type: 'text_line', visible: true}});
    const schemaPromise = Promise.resolve({
        setting: {mandatory: true, tags: [], type: 'checkbox'},
    });
    const jsonSchemaPromise = Promise.resolve({type: 'object', required: ['setting']});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        onChange: changeSpy,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types,
        value: [{type: 'default'}, {type: 'default'}],
    });

    await user.click(screen.getByRole('button', {name: 'settings-1'}));
    await Promise.all([schemaPromise, jsonSchemaPromise]);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.apply'}));

    expect(screen.getByTestId('form-overlay')).toBeInTheDocument();
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Should display and update correct icons based on block settings data and schema', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        text1: {label: 'Text 1', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
    });
    const schemaPromise = Promise.resolve({
        setting: {
            tags: [{attributes: {icon: 'su-hide'}, name: 'sulu.block_setting_icon'}],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types,
        value: [{text1: 'Test 1', type: 'default', settings: {setting: true}}],
    });

    await Promise.all([schemaPromise, jsonSchemaPromise]);
    expect(screen.getByLabelText('su-hide')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'settings-0'}));
    await user.click(screen.getByRole('button', {name: 'set-setting-false'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.apply'}));

    expect(screen.queryByLabelText('su-hide')).not.toBeInTheDocument();
});

test('Should display correct icons based on visibleCondition', async() => {
    const formInspector = createFormInspector();
    conditionDataProviderRegistry.add(() => ({__locale: 'de'}));
    const types = getDefaultTypes({
        text1: {label: 'Text 1', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
    });
    const schemaPromise = Promise.resolve({
        setting: {
            tags: [{
                attributes: {icon: 'su-hide', visibleCondition: '__locale == "de" && text1 == "Test 1"'},
                name: 'sulu.block_setting_icon',
            }],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const {rerender} = renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types,
        value: [{text1: 'Test 1', type: 'default', settings: {setting: true}}],
    });

    await Promise.all([schemaPromise, jsonSchemaPromise]);
    expect(screen.getByLabelText('su-hide')).toBeInTheDocument();

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={[{text1: 'Test 2', type: 'default', settings: {setting: true}}]}
        />
    );

    expect(screen.queryByLabelText('su-hide')).not.toBeInTheDocument();
});

test('Should recalculate visibleCondition only of changed blocks', async() => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        text1: {label: 'Text 1', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
        text2: {label: 'Text 2', tags: [{name: 'sulu.block_preview'}], type: 'text_line'},
    });
    const schemaPromise = Promise.resolve({
        setting: {
            tags: [{
                attributes: {
                    icon: 'su-hide',
                    visibleCondition: '(__provider.text1 != "disabled" && text1 == "Test 1") || text2 == "Test 2"',
                },
                name: 'sulu.block_setting_icon',
            }],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const {rerender} = renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types,
        value: [{text1: 'Test 1', type: 'default'}, {text2: 'Test 2', type: 'default'}],
    });

    await Promise.all([schemaPromise, jsonSchemaPromise]);
    expect(within(screen.getByTestId('block-icons-0')).getByLabelText('su-hide')).toBeInTheDocument();
    expect(within(screen.getByTestId('block-icons-1')).getByLabelText('su-hide')).toBeInTheDocument();

    conditionDataProviderRegistry.add(() => ({__provider: {text1: 'disabled'}}));

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={[{text1: 'Test 1', type: 'default'}, {text2: 'Test 4', type: 'default'}]}
        />
    );

    expect(within(screen.getByTestId('block-icons-0')).getByLabelText('su-hide')).toBeInTheDocument();
    expect(within(screen.getByTestId('block-icons-1')).queryByLabelText('su-hide')).not.toBeInTheDocument();
});

test('Should destroy the block settings form-store on unmount', () => {
    const destroySpy = jest.fn();
    const createSpy = jest.spyOn(memoryFormStoreFactory, 'createFromFormKey').mockReturnValue({
        data: {},
        dirty: false,
        schema: {},
        destroy: destroySpy,
    });
    const formInspector = createFormInspector();

    const {unmount} = renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}},
        types: getDefaultTypes({text: {label: 'Text', type: 'text_line'}}),
    });

    unmount();

    expect(destroySpy).toHaveBeenCalledWith();
    createSpy.mockRestore();
});

test('Should show correct value in type select after type is changed', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = {
        default: {title: 'Default', form: {text: {label: 'Text', type: 'text_line'}}},
        other: {title: 'Other', form: {other_text: {label: 'Other Text', type: 'text_line'}}},
    };

    renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        types,
        value: [{type: 'default'}],
    });

    expect(screen.getByTestId('block-type-0')).toHaveTextContent('default');
    await user.click(screen.getByRole('button', {name: 'change-to-other'}));
    expect(screen.getByTestId('block-type-0')).toHaveTextContent('other');
});

test('Should set correct default values for multiple single_select in blocks', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();
    const types = getDefaultTypes({
        position_center: {label: 'Position Center', type: 'single_select', options: {}},
        position_left: {
            label: 'Position Left',
            type: 'single_select',
            options: {default_value: {name: 'default_value', type: 'string', value: 'left'}},
        },
        position_right: {
            label: 'Position Right',
            type: 'single_select',
            options: {default_value: {name: 'default_value', type: 'string', value: 'right'}},
        },
    });
    fieldRegistry.get.mockReturnValue(SingleSelect);
    const changeSpy = jest.fn();

    renderFieldBlocks({
        defaultType: 'default',
        formInspector,
        minOccurs: 1,
        onChange: changeSpy,
        types,
        value: observable([{type: 'default'}]),
    });

    await user.click(screen.getByRole('button', {name: 'expand-0'}));

    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith(
        [{position_left: 'left', position_right: 'right', type: 'default'}],
        {isDefaultValue: true}
    ));
});

test('Throw error if no default type are passed', () => {
    const formInspector = createFormInspector();
    expect(() => renderFieldBlocks({formInspector})).toThrow('The "block" field type needs a defaultType!');
});

test('Throw error if no types are passed', () => {
    const formInspector = createFormInspector();
    expect(() => renderFieldBlocks({defaultType: 'editor', formInspector}))
        .toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if empty type array is passed', () => {
    const formInspector = createFormInspector();
    expect(() => renderFieldBlocks({defaultType: 'editor', formInspector, value: []}))
        .toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if passed settings_form_key schema option is not a string', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({nothing: {label: 'Nothing', type: 'phone'}});

    expect(() => renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {settings_form_key: {name: 'settings_form_key', value: []}},
        types,
        value: [],
    })).toThrow('The "block" field types only accepts strings as "settings_form_key" schema option!');
});

test('Throw error if passed add_button_text schema option is not a string', () => {
    const formInspector = createFormInspector();
    const types = getDefaultTypes({nothing: {label: 'Nothing', type: 'phone'}});

    expect(() => renderFieldBlocks({
        defaultType: 'editor',
        formInspector,
        schemaOptions: {add_button_text: {name: 'add_button_text', title: ([]: any)}},
        types,
        value: [],
    })).toThrow('The "block" field types only accepts strings as "add_button_text" schema option!');
});
