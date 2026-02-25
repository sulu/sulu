/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, waitFor} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../utils/TestHelper/fieldTypeDefaultProps';
import FieldBlocks from '../FieldBlocks';
import BlockCollection from '../../../components/BlockCollection';
import FormOverlay from '../../FormOverlay';
import {memoryFormStoreFactory} from '../../Form';
import blockIdGenerator from '../../../services/blockIdGenerator';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../../../components/BlockCollection', () => jest.fn(() => <div data-testid="block-collection" />));
jest.mock('../../FormOverlay', () => jest.fn(() => <div data-testid="form-overlay" />));
jest.mock('../../Form', () => ({
    memoryFormStoreFactory: {
        createFromFormKey: jest.fn(),
    },
}));
jest.mock('../../../services/blockIdGenerator', () => ({
    generateBlockIds: jest.fn(),
}));
jest.mock('../../../stores/snackbarStore', () => ({
    add: jest.fn(),
}));
jest.mock('../../Form/registries/conditionDataProviderRegistry', () => ({
    getAll: jest.fn(() => []),
}));
jest.mock('../registries/blockPreviewTransformerRegistry', () => ({
    has: jest.fn(() => false),
    get: jest.fn(),
    blockPreviewTransformerKeysByPriority: [],
}));
jest.mock('../FieldRenderer', () => jest.fn(() => <div data-testid="field-renderer" />));

const BlockCollectionMock = BlockCollection;
const FormOverlayMock = FormOverlay;
const createFromFormKeyMock = memoryFormStoreFactory.createFromFormKey;

const formInspector = {
    locale: undefined,
    options: {},
};

const baseTypes = {
    default: {
        title: 'Default',
        form: {
            title: {
                type: 'text_line',
            },
        },
    },
    editor: {
        title: 'Editor',
        form: {
            text: {
                type: 'text_area',
            },
        },
    },
};

const baseValue = [
    {type: 'default', title: 'Block 1'},
    {type: 'editor', text: 'Block 2'},
];

const createProps = (overrides = {}) => ({
    ...fieldTypeDefaultProps,
    defaultType: 'default',
    formInspector,
    onChange: jest.fn(),
    onFinish: jest.fn(),
    schemaOptions: {},
    types: baseTypes,
    value: baseValue,
    ...overrides,
});

beforeEach(() => {
    jest.clearAllMocks();
    createFromFormKeyMock.mockReturnValue({
        data: {setting: true},
        destroy: jest.fn(),
        dirty: false,
        schema: {},
    });
});

test('renders block collection with mapped type titles', () => {
    const props = createProps();
    const {asFragment} = render(<FieldBlocks {...props} />);

    expect(BlockCollectionMock).toHaveBeenCalledWith(expect.objectContaining({
        defaultType: 'default',
        disabled: false,
        types: {
            default: 'Default',
            editor: 'Editor',
        },
        value: baseValue,
    }), {});
    expect(asFragment()).toMatchSnapshot();
});

test('forwards block change and sort callbacks', () => {
    const onChange = jest.fn();
    const onFinish = jest.fn();
    const props = createProps({onChange, onFinish});
    render(<FieldBlocks {...props} />);

    getLatestMockProps(BlockCollectionMock).onChange([{type: 'default'}]);
    getLatestMockProps(BlockCollectionMock).onSortEnd();

    expect(onChange).toHaveBeenCalledWith([{type: 'default'}]);
    expect(onFinish).toHaveBeenCalled();
});

test('uses block id generator when configured', () => {
    const props = createProps({
        schemaOptions: {
            block_id_generator: {
                value: true,
            },
        },
    });

    render(<FieldBlocks {...props} />);

    expect(getLatestMockProps(BlockCollectionMock).generateBlockIds).toBe(blockIdGenerator.generateBlockIds);
});

test('opens settings overlay and applies settings to selected block', async() => {
    const onChange = jest.fn();
    const props = createProps({
        onChange,
        schemaOptions: {
            settings_form_key: {
                value: 'content_block_settings',
            },
        },
    });
    render(<FieldBlocks {...props} />);

    await waitFor(() => expect(createFromFormKeyMock).toHaveBeenCalledWith(
        'content_block_settings',
        {},
        formInspector.locale,
        undefined,
        formInspector.options
    ));

    const blockCollectionProps = getLatestMockProps(BlockCollectionMock);
    blockCollectionProps.onSettingsClick(1);

    await waitFor(() => expect(FormOverlayMock).toHaveBeenCalled());
    const overlayProps = getLatestMockProps(FormOverlayMock);
    expect(overlayProps.open).toBe(true);

    overlayProps.onConfirm();
    expect(onChange).toHaveBeenLastCalledWith([
        {type: 'default', title: 'Block 1'},
        {type: 'editor', text: 'Block 2', settings: {setting: true}},
    ]);
});

test('passes confirmDisabled based on settings form dirty state', async() => {
    createFromFormKeyMock.mockReturnValue({
        data: {},
        destroy: jest.fn(),
        dirty: true,
        schema: {},
    });
    const props = createProps({
        schemaOptions: {
            settings_form_key: {
                value: 'content_block_settings',
            },
        },
    });

    render(<FieldBlocks {...props} />);
    getLatestMockProps(BlockCollectionMock).onSettingsClick(0);

    await waitFor(() => expect(FormOverlayMock).toHaveBeenCalled());
    const overlayProps = getLatestMockProps(FormOverlayMock);
    expect(overlayProps.confirmDisabled).toBe(false);
});

test('throws if defaultType is missing', () => {
    const props = createProps({defaultType: undefined});

    expect(() => render(<FieldBlocks {...props} />))
        .toThrow('The "block" field type needs a defaultType!');
});

test('throws if types are missing', () => {
    const props = createProps({types: undefined});

    expect(() => render(<FieldBlocks {...props} />))
        .toThrow('The "block" field type needs at least one type to be configured!');
});
