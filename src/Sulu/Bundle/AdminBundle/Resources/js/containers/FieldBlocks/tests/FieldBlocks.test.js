// @flow
import React from 'react';
import {render, screen, act, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import Router from '../../../services/Router';
import fieldTypeDefaultProps from '../../../utils/TestHelper/fieldTypeDefaultProps';
import FieldBlocks from '../FieldBlocks';
import FormInspector from '../../Form/FormInspector';
import metadataStore from '../../Form/stores/metadataStore';
import ResourceFormStore from '../../Form/stores/ResourceFormStore';
import ResourceStore from '../../../stores/ResourceStore';
import blockPreviewTransformerRegistry from '../registries/blockPreviewTransformerRegistry';
import fieldRegistry from '../../Form/registries/fieldRegistry';
import SingleSelect from '../../Form/fields/SingleSelect';
import conditionDataProviderRegistry from '../../Form/registries/conditionDataProviderRegistry';
import BlockCollection from '../../../components/BlockCollection';
import FieldRenderer from '../FieldRenderer';
import FormOverlay from '../../FormOverlay';
import {memoryFormStoreFactory} from '../../Form';

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

jest.mock('../registries/blockPreviewTransformerRegistry', () => ({
    has: jest.fn(),
    get: jest.fn(),
    blockPreviewTransformerKeysByPriority: [],
}));

jest.mock('../../../components/BlockCollection', () => {
    const React = require('react');
    const ActualBlockCollection = jest.requireActual('../../../components/BlockCollection').default;

    return jest.fn((props) => <ActualBlockCollection {...props} />);
});

jest.mock('../FieldRenderer', () => {
    const React = require('react');
    const ActualFieldRenderer = jest.requireActual('../FieldRenderer').default;

    return jest.fn((props) => <ActualFieldRenderer {...props} />);
});

jest.mock('../../FormOverlay', () => {
    const React = require('react');
    const ActualFormOverlay = jest.requireActual('../../FormOverlay').default;

    return jest.fn((props) => <ActualFormOverlay {...props} />);
});

beforeEach(() => {
    blockPreviewTransformerRegistry.has.mockClear();
    blockPreviewTransformerRegistry.get.mockClear();
    (BlockCollection: any).mockClear();
    (FieldRenderer: any).mockClear();
    (FormOverlay: any).mockClear();
    conditionDataProviderRegistry.clear();
    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [];
});

const getLatestMockProps = (mockComponent: any) => {
    const calls = mockComponent.mock.calls;

    return calls[calls.length - 1][0];
};

test('Render collapsed blocks with block previews', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
                something: {
                    label: 'Something',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_area',
                },
                nothing: {
                    label: 'Nothing',
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [
                {attributes: {icon: 'su-eye'}, name: 'sulu.block_setting_icon'},
            ],
            type: 'checkbox',
        },
        section: {
            items: {
                section_setting: {
                    tags: [
                        {attributes: {icon: 'su-hide'}, name: 'sulu.block_setting_icon'},
                    ],
                    type: 'checkbox',
                },
            },
            type: 'section',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const value = [
        {
            text1: 'Test 1',
            text2: undefined,
            something: 'Test 3',
            type: 'default',
            settings: {
                setting: true,
            },
        },
        {
            text1: 'Test 4',
            text2: undefined,
            something: 'Test 6',
            type: 'default',
            settings: {
                section_setting: true,
            },
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return {
                    transform: function Transformer(value) {
                        return <p>{value}</p>;
                    },
                };
        }
    });

    const {container} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    await act(async() => {
        await Promise.all([schemaPromise, jsonSchemaPromise]);
    });

    expect(container).toMatchSnapshot();
});

test('Render collapsed blocks with block previews and sections', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                section1: {
                    label: 'Section',
                    type: 'section',
                    items: {
                        text1: {
                            label: 'Text 1',
                            tags: [
                                {name: 'sulu.block_preview'},
                            ],
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            tags: [
                                {name: 'sulu.block_preview'},
                            ],
                            type: 'text_line',
                        },
                        something: {
                            label: 'Something',
                            tags: [
                                {name: 'sulu.block_preview'},
                            ],
                            type: 'text_area',
                        },
                        nothing: {
                            label: 'Nothing',
                            type: 'text_line',
                        },
                    },
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text1: 'Test 1',
            text2: undefined,
            something: 'Test 2',
            type: 'default',
        },
        {
            text1: undefined,
            text2: 'Test 3',
            something: 'Test 4',
            type: 'default',
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return true;
            case 'text_area':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return {
                    transform: function Transformer(value) {
                        return <p>{value}</p>;
                    },
                };
            case 'text_area':
                return {
                    transform: function Transformer(value) {
                        return <p>{value}</p>;
                    },
                };
        }
    });

    const {container} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render collapsed blocks with block previews without tags and with sections', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                section1: {
                    label: 'Section',
                    type: 'section',
                    items: {
                        nothing: {
                            label: 'Nothing',
                            type: 'phone',
                        },
                        text1: {
                            label: 'Text 1',
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            type: 'media_selection',
                        },
                        something: {
                            label: 'Text 3',
                            type: 'text_editor',
                        },
                    },
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            nothing: 'phone',
            text1: 'Test 1',
            text2: 'Test 2',
            something: 'Test 3',
            type: 'default',
        },
        {
            nothing: 'phone',
            text1: 'Test 4',
            text2: 'Test 5',
            something: 'Test 6',
            type: 'default',
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'media_selection':
            case 'phone':
            case 'text_line':
            case 'text_editor':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'phone':
                return {
                    transform: function Transformer() {
                        return <p>phone</p>;
                    },
                };
            case 'media_selection':
                return {
                    transform: function Transformer() {
                        return <p>media_selection</p>;
                    },
                };
            case 'text_line':
                return {
                    transform: function Transformer() {
                        return <p>text_line</p>;
                    },
                };
            case 'text_editor':
                return {
                    transform: function Transformer() {
                        return <p>text_editor</p>;
                    },
                };
        }
    });

    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [
        'media_selection',
        'text_line',
        'text_editor',
    ];

    const {container} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render collapsed blocks with block previews without tags', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                nothing: {
                    label: 'Nothing',
                    type: 'phone',
                },
                text1: {
                    label: 'Text 1',
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    type: 'media_selection',
                },
                something: {
                    label: 'Text 3',
                    type: 'text_editor',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            nothing: 'phone',
            text1: 'Test 1',
            text2: 'Test 2',
            something: 'Test 3',
            type: 'default',
        },
        {
            nothing: 'phone',
            text1: 'Test 4',
            text2: 'Test 5',
            something: 'Test 6',
            type: 'default',
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'media_selection':
            case 'phone':
            case 'text_line':
            case 'text_editor':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'phone':
                return {
                    transform: function Transformer() {
                        return <p>phone</p>;
                    },
                };
            case 'media_selection':
                return {
                    transform: function Transformer() {
                        return <p>media_selection</p>;
                    },
                };
            case 'text_line':
                return {
                    transform: function Transformer() {
                        return <p>text_line</p>;
                    },
                };
            case 'text_editor':
                return {
                    transform: function Transformer() {
                        return <p>text_editor</p>;
                    },
                };
        }
    });

    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [
        'media_selection',
        'text_line',
        'text_editor',
    ];

    const {container} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render collapsed blocks with block previews', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview', priority: -100},
                    ],
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
                something: {
                    label: 'Text 3',
                    tags: [
                        {name: 'sulu.block_preview', priority: 100},
                    ],
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text1: 'Test 1',
            text2: 'Test 2',
            something: 'Test 3',
            type: 'default',
        },
        {
            text1: 'Test 4',
            text2: 'Test 5',
            something: 'Test 6',
            type: 'default',
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return {
                    transform: function Transformer(value) {
                        return <p>{value}</p>;
                    },
                };
        }
    });

    const {container} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render block with schema', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text1: 'Test 1',
            text2: 'Test 2',
            type: 'default',
        },
        {
            text1: 'Test 3',
            text2: 'Test 4',
            type: 'default',
        },
    ];

    const {container} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Call not onChange on componentDidUpdate when new types are the same', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const changeSpy = jest.fn();

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={{
                default: {
                    title: 'Default',
                    form: {
                        text1: {
                            label: 'Text 1',
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            type: 'text_line',
                        },
                    },
                },
            }}
            value={[
                {
                    text1: 'Test 1',
                    text2: 'Test 2',
                    type: 'default',
                },
                {
                    text1: 'Test 3',
                    text2: 'Test 4',
                    type: 'default',
                },
            ]}
        />
    );

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={{
                default: {
                    title: 'Default',
                    form: {
                        text1: {
                            label: 'Text 1 a',
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2 b',
                            type: 'text_line',
                        },
                    },
                },
            }}
            value={[
                {
                    text1: 'Test 1 a',
                    text2: 'Test 2 b',
                    type: 'default',
                },
                {
                    text1: 'Test 3 a',
                    text2: 'Test 4 c',
                    type: 'default',
                },
            ]}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Call onChange on componentDidUpdate when type not longer exist', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const changeSpy = jest.fn();

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={{
                default: {
                    title: 'Default',
                    form: {
                        text1: {
                            label: 'Text 1',
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            type: 'text_line',
                        },
                    },
                },
            }}
            value={[
                {
                    text1: 'Test 1',
                    text2: 'Test 2',
                    type: 'default',
                },
                {
                    text1: 'Test 3',
                    text2: 'Test 4',
                    type: 'default',
                },
            ]}
        />
    );

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
                        text1: {
                            label: 'Text 1',
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            type: 'text_line',
                        },
                    },
                },
            }}
            value={[
                {
                    text1: 'Test 1',
                    text2: 'Test 2',
                    type: 'not-exist',
                },
                {
                    text1: 'Test 3',
                    text2: 'Test 4',
                    type: 'default',
                },
            ]}
        />
    );

    expect(changeSpy).toBeCalledWith([
        {
            text1: 'Test 1',
            text2: 'Test 2',
            type: 'new',
        },
        {
            text1: 'Test 3',
            text2: 'Test 4',
            type: 'new',
        },
    ]);
});

test('Render block with schema and error on fields already being modified', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text: 'Test1',
            type: 'default',
        },
        {
            text: 'T2',
            type: 'default',
        },
        {
            text: 'T3',
            type: 'default',
        },
    ];

    const error = [
        undefined,
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
    ];

    formInspector.isFieldModified.mockImplementation((dataPath) => {
        return dataPath === '/block/0/text' || dataPath === '/block/1/text';
    });

    const {container} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath="/block"
            defaultType="editor"
            error={error}
            formInspector={formInspector}
            schemaPath="/block"
            types={types}
            value={value}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render block with schema and error on fields already being modified', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text: 'Test1',
            type: 'default',
        },
        {
            text: 'T2',
            type: 'default',
        },
        {
            text: 'T3',
            type: 'default',
        },
    ];

    const error = [
        undefined,
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
    ];

    const {container} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            error={error}
            formInspector={formInspector}
            showAllErrors={true}
            types={types}
            value={value}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Should correctly pass props to the BlockCollection', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [];

    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={1}
            schemaOptions={{
                add_button_text: {name: 'add_button_text', title: 'custom-add-text'},
                paste_button_text: {name: 'paste_button_text', title: 'custom-paste-text'}}
            }
            types={types}
            value={value}
        />
    );

    await waitFor(() => {
        expect(BlockCollection).toHaveBeenCalledWith(
            expect.objectContaining({
                addButtonText: 'custom-add-text',
                pasteButtonText: 'custom-paste-text',
                collapsable: undefined,
                disabled: true,
                maxOccurs: 2,
                minOccurs: 1,
                movable: undefined,
                types: {
                    default: 'Default',
                },
                value: [],
            }),
            expect.anything()
        );
    });
});

test('Should pass collapsable and movable props to the BlockCollection', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };

    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={1}
            schemaOptions={{movable: {name: 'movable', value: false}, collapsable: {name: 'collapsable', value: false}}}
            types={types}
            value={[]}
        />
    );

    expect(BlockCollection).toHaveBeenCalledWith(
        expect.objectContaining({
            collapsable: false,
            movable: false,
        }),
        expect.anything()
    );
});

test('Should pass new value to the BlockCollection if value prop is updated', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={0}
            types={types}
            value={[]}
        />
    );
    await waitFor(() => {
        expect(BlockCollection).toHaveBeenLastCalledWith(
            expect.objectContaining({value: []}),
            expect.anything()
        );
    });

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={0}
            types={types}
            value={[{type: 'default', text: 'One'}]}
        />
    );
    expect(BlockCollection).toHaveBeenLastCalledWith(
        expect.objectContaining({value: [{type: 'default', text: 'One'}]}),
        expect.anything()
    );

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={0}
            types={types}
            value={observable([{type: 'default', text: 'Two'}])}
        />
    );
    expect(BlockCollection).toHaveBeenLastCalledWith(
        expect.objectContaining({value: [{type: 'default', text: 'Two'}]}),
        expect.anything()
    );
});

test('Should pass correct data and value and router to FieldRenderer', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const router = new Router();

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    type: 'text_line',
                },
            },
        },
    };
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const data = {
        title: 'Test',
    };

    const value = [
        {
            title: 'Test 1',
            type: 'default',
        },
        {
            title: 'Test 2',
            type: 'default',
        },
    ];

    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            data={data}
            dataPath=""
            defaultType="editor"
            formInspector={formInspector}
            router={router}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    // Trigger renderBlockContent which triggers FieldRenderer
    const {renderBlockContent} = (BlockCollection: any).mock.calls[0][0];

    // Render first block
    render(renderBlockContent(value[0], 'default', 0, true));
    expect(FieldRenderer).toHaveBeenCalledWith(
        expect.objectContaining({
            data,
            value: value[0],
            router,
        }),
        expect.anything()
    );

    // Render second block
    render(renderBlockContent(value[1], 'default', 1, true));
    expect(FieldRenderer).toHaveBeenCalledWith(
        expect.objectContaining({
            data,
            value: value[1],
            router,
        }),
        expect.anything()
    );
});

test('Should call onFinish when a field from the child renderer has finished editing', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const finishSpy = jest.fn();
    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath=""
            defaultType="editor"
            fieldTypeOptions={{}}
            formInspector={formInspector}
            onFinish={finishSpy}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    const {renderBlockContent} = (BlockCollection: any).mock.calls[0][0];
    const {onFieldFinish} = renderBlockContent(value[0], 'default', 0, true).props;

    act(() => {
        onFieldFinish();
    });

    expect(finishSpy).toBeCalledWith();
});

test ('Should set nested properties in handleBlockChange and call onChange with new values', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const changeSpy = jest.fn();
    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath=""
            defaultType="editor"
            fieldTypeOptions={{}}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    const {renderBlockContent} = (BlockCollection: any).mock.calls[0][0];
    const {onChange} = renderBlockContent(value[0], 'default', 0, true).props;

    act(() => {
        onChange(0, 'options/test1', 'value1');
    });

    const expectedArray1 = [{type: 'default', options: {test1: 'value1'}}];
    expect(changeSpy).toBeCalledWith(expectedArray1, undefined);

    act(() => {
        onChange(0, 'options/test2/test3', 'value2');
    });
    const expectedArray2 = [{type: 'default', options: {test1: 'value1', test2: {test3: 'value2'}}}];
    expect(changeSpy).toBeCalledWith(expectedArray2, undefined);
});

test('Should pass context through handleBlockChange to onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const changeSpy = jest.fn();
    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath=""
            defaultType="default"
            fieldTypeOptions={{}}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    const {renderBlockContent} = (BlockCollection: any).mock.calls[0][0];
    const {onChange} = renderBlockContent(value[0], 'default', 0, true).props;
    onChange(0, 'alignment', 'left', {isDefaultValue: true});

    expect(changeSpy).toBeCalledWith(
        [{type: 'default', alignment: 'left'}],
        {isDefaultValue: true}
    );
});

test('Should call onFinish when the order of the blocks has changed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const finishSpy = jest.fn();
    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            onFinish={finishSpy}
            types={types}
            value={value}
        />
    );

    const {onSortEnd} = (BlockCollection: any).mock.calls[0][0];
    act(() => {
        onSortEnd(0, 2);
    });

    expect(finishSpy).toBeCalledWith();
});

test('Should open and close block settings overlay close button is clicked', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(screen.queryByRole('heading', {name: 'sulu_admin.block_settings'})).not.toBeInTheDocument();

    const {onSettingsClick} = (BlockCollection: any).mock.calls[0][0];
    act(() => {
        onSettingsClick(0);
    });

    expect(screen.getByRole('heading', {name: 'sulu_admin.block_settings'})).toBeInTheDocument();

    const {onClose} = (FormOverlay: any).mock.calls[0][0];
    act(() => {
        onClose();
    });

    // We need to rerender to see the effect of state change if it's internal
    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(screen.queryByRole('heading', {name: 'sulu_admin.block_settings'})).not.toBeInTheDocument();
});

test('Should open and close block settings overlay when confirm button is clicked with changed data', async() => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [
        {type: 'default', settings: {setting: false}},
        {type: 'default', settings: {setting: false}},
    ];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(metadataStore.getSchema).toBeCalledWith('page_block_settings', undefined, undefined);
    expect(screen.queryByRole('heading', {name: 'sulu_admin.block_settings'})).not.toBeInTheDocument();

    const {onSettingsClick} = (BlockCollection: any).mock.calls[0][0];
    act(() => {
        onSettingsClick(1);
    });

    expect(screen.getByRole('heading', {name: 'sulu_admin.block_settings'})).toBeInTheDocument();

    await act(async() => {
        await Promise.all([schemaPromise, jsonSchemaPromise]);
    });

    const {formStore} = (FormOverlay: any).mock.calls[0][0];
    formStore.change('setting', true);

    const {onConfirm} = (FormOverlay: any).mock.calls[0][0];
    act(() => {
        onConfirm();
    });

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(screen.queryByRole('heading', {name: 'sulu_admin.block_settings'})).not.toBeInTheDocument();
    expect(changeSpy).toBeCalledWith(
        [{type: 'default', settings: {setting: false}}, {type: 'default', settings: {setting: true}}]
    );
});

test('Should destroy create new formstore when block settings overlay is opened for another block', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [
        {type: 'default'},
        {type: 'default'},
    ];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(screen.queryByRole('heading', {name: 'sulu_admin.block_settings'})).not.toBeInTheDocument();

    // Open first block
    const {onSettingsClick} = (BlockCollection: any).mock.calls[0][0];
    act(() => {
        onSettingsClick(0);
    });

    expect(screen.getByRole('heading', {name: 'sulu_admin.block_settings'})).toBeInTheDocument();
    const firstFormStore = (FormOverlay: any).mock.calls[0][0].formStore;

    // Close first block
    const {onClose} = (FormOverlay: any).mock.calls[0][0];
    act(() => {
        onClose();
    });

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );
    expect(screen.queryByRole('heading', {name: 'sulu_admin.block_settings'})).not.toBeInTheDocument();

    // Open second block
    act(() => {
        // Need to get the fresh callback from the latest render
        getLatestMockProps(BlockCollection).onSettingsClick(1);
    });

    expect(screen.getByRole('heading', {name: 'sulu_admin.block_settings'})).toBeInTheDocument();
    const secondFormStore = getLatestMockProps(FormOverlay).formStore;

    expect(secondFormStore).not.toBe(firstFormStore);
});

test('Should not close block settings overlay when confirm button is clicked with invalid data', async() => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };
    const value = [
        {type: 'default'},
        {type: 'default'},
    ];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            mandatory: true,
            tags: [],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({type: 'object', required: ['setting']});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    // Open settings for block 1
    const {onSettingsClick} = (BlockCollection: any).mock.calls[0][0];
    act(() => {
        onSettingsClick(1);
    });

    await act(async() => {
        await Promise.all([schemaPromise, jsonSchemaPromise]);
    });

    const formStore = getLatestMockProps(FormOverlay).formStore;
    // Mock validate to return false (invalid)
    formStore.validate = jest.fn().mockReturnValue(false);

    await userEvent.click(screen.getByRole('button', {name: 'sulu_admin.apply'}));

    expect(screen.getByRole('heading', {name: 'sulu_admin.block_settings'})).toBeInTheDocument();
    expect(changeSpy).not.toBeCalled();
});

test('Should display and update correct icons based on block settings data and schema', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [
                {attributes: {icon: 'su-hide'}, name: 'sulu.block_setting_icon'},
            ],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const value = [
        {
            text1: 'Test 1',
            type: 'default',
            settings: {
                setting: true,
            },
        },
    ];

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    // Trigger settings click
    const {onSettingsClick} = (BlockCollection: any).mock.calls[0][0];
    act(() => {
        onSettingsClick(0);
    });

    await act(async() => {
        await Promise.all([schemaPromise, jsonSchemaPromise]);
    });

    // Verify icons were passed to BlockCollection
    // Since we mocked BlockCollection, we check the props it received in the LAST render
    // But icons are computed asynchronously after schema loads, so we need to wait/retry or check the latest call

    // Note: The logic in FieldBlocks updates icons when settings form store changes or when props change.
    // The "icons" prop on BlockCollection should be populated.

    // We can't easily check icons inside the BlockCollection mock unless we inspect calls.
    // The original test checked for 'Icon[name="su-hide"]' inside the block, but that logic is inside FieldBlocks
    // calculating icons and passing them to BlockCollection.

    expect(getLatestMockProps(BlockCollection).icons[0]).toEqual(['su-hide']);

    // Change value in form store
    const formStore = (FormOverlay: any).mock.calls[0][0].formStore;
    formStore.data.setting = false; // Simulate uncheck

    const {onConfirm} = (FormOverlay: any).mock.calls[0][0];
    act(() => {
        onConfirm();
    });

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    // The icon should be gone
    expect(getLatestMockProps(BlockCollection).icons[0]).toEqual([]);
});

test('Should display correct icons based on visibleCondition', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    conditionDataProviderRegistry.add(() => ({__locale: 'de'}));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [
                {
                    attributes: {
                        icon: 'su-hide',
                        visibleCondition: '__locale == "de" && text1 == "Test 1"',
                    },
                    name: 'sulu.block_setting_icon',
                },
            ],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const value = [
        {
            text1: 'Test 1',
            type: 'default',
            settings: {
                setting: true,
            },
        },
    ];

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    await act(async() => {
        await Promise.all([schemaPromise, jsonSchemaPromise]);
    });

    expect(getLatestMockProps(BlockCollection).icons[0]).toEqual(['su-hide']);

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={[
                {
                    text1: 'Test 2',
                    type: 'default',
                    settings: {
                        setting: true,
                    },
                },
            ]}
        />
    );

    expect(getLatestMockProps(BlockCollection).icons[0]).toEqual([]);
});

test('Should recalculate visibleCondition only of changed blocks', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [
                {
                    attributes: {
                        icon: 'su-hide',
                        visibleCondition: '(__provider.text1 != "disabled" && text1 == "Test 1") || text2 == "Test 2"',
                    },
                    name: 'sulu.block_setting_icon',
                },
            ],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const value = [
        {
            text1: 'Test 1',
            type: 'default',
        },
        {
            text2: 'Test 2',
            type: 'default',
        },
    ];

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    await act(async() => {
        await Promise.all([schemaPromise, jsonSchemaPromise]);
    });

    expect(getLatestMockProps(BlockCollection).icons[0]).toEqual(['su-hide']);
    expect(getLatestMockProps(BlockCollection).icons[1]).toEqual(['su-hide']);

    // add a provider to make the visibleCondition of the first block = false
    conditionDataProviderRegistry.add(() => ({
        __provider: {
            'text1': 'disabled',
        },
    }));

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={[
                {
                    text1: 'Test 1',
                    type: 'default',
                },
                {
                    text2: 'Test 4',
                    type: 'default',
                },
            ]}
        />
    );

    // first block is still visible, because it was not reevaluated as only the second block changed
    expect(getLatestMockProps(BlockCollection).icons[0]).toEqual(['su-hide']);
    expect(getLatestMockProps(BlockCollection).icons[1]).toEqual([]);
});

test('Should destroy the block settings form-store on unmount', () => {
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    // Spy on the factory method
    const destroySpy = jest.fn();
    const createSpy = jest.spyOn(memoryFormStoreFactory, 'createFromFormKey').mockReturnValue({
        destroy: destroySpy,
        data: {},
        validate: jest.fn().mockReturnValue(true),
        change: jest.fn(),
    });

    const {unmount} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
        />
    );

    // Trigger creation of form store (by clicking settings)?
    // Wait, the original test implies it's created on mount if schemaOptions is set?
    // No, FieldBlocks creates it lazily when settings are opened OR eagerly?
    // Looking at FieldBlocks.js: constructor calls createBlockSettingsFormStore if settingsFormKey is present?
    // Let's check FieldBlocks.js logic.
    // It creates it in componentDidMount -> createBlockSettingsFormStore.

    expect(createSpy).toHaveBeenCalled();

    unmount();

    expect(destroySpy).toHaveBeenCalled();

    createSpy.mockRestore();
});

test('Should show correct value in type select after type is changed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
        other: {
            title: 'Other',
            form: {
                other_text: {
                    label: 'Other Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const {rerender} = render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(screen.getByText('Default')).toBeInTheDocument();

    rerender(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={[{type: 'other'}]}
        />
    );

    expect(screen.getByText('Other')).toBeInTheDocument();
});

test('Should set correct default values for multiple single_select in blocks', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                position_center: {
                    label: 'Position Center',
                    type: 'single_select',
                    options: {
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
                position_left: {
                    label: 'Position Left',
                    type: 'single_select',
                    options: {
                        default_value: {
                            name: 'default_value',
                            type: 'string',
                            value: 'left',
                        },
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
                position_right: {
                    label: 'Position Right',
                    type: 'single_select',
                    options: {
                        default_value: {
                            name: 'default_value',
                            type: 'string',
                            value: 'right',
                        },
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});
    fieldRegistry.get.mockReturnValue(SingleSelect);

    const changeSpy = jest.fn();

    render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            minOccurs={1}
            onChange={changeSpy}
            types={types}
            value={observable([{type: 'default'}])}
        />
    );

    const expectedWithDefaults = [
        {
            'position_left': 'left',
            'position_right': 'right',
            'type': 'default',
        },
    ];

    const block = screen.getByRole('switch');
    act(() => {
        block.click();
    });

    await waitFor(() => {
        expect(changeSpy).toBeCalledWith(expectedWithDefaults, {isDefaultValue: true});
    });
});

test('Throw error if no default type are passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    )).toThrow('The "block" field type needs a defaultType!');
});

test('Throw error if no types are passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
        />
    )).toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if empty type array is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => render(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            value={[]}
        />
    )).toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if passed settings_form_key schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                nothing: {
                    label: 'Nothing',
                    type: 'phone',
                },
            },
        },
    };

    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <FieldBlocks
                {...fieldTypeDefaultProps}
                defaultType="editor"
                formInspector={formInspector}
                schemaOptions={{settings_form_key: {name: 'settings_form_key', value: []}}}
                types={types}
                value={[]}
            />
        )).toThrow('The "block" field types only accepts strings as "settings_form_key" schema option!');
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Throw error if passed add_button_text schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                nothing: {
                    label: 'Nothing',
                    type: 'phone',
                },
            },
        },
    };

    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <FieldBlocks
                {...fieldTypeDefaultProps}
                defaultType="editor"
                formInspector={formInspector}
                schemaOptions={{add_button_text: {name: 'add_button_text', title: ([]: any)}}}
                types={types}
                value={[]}
            />
        )).toThrow('The "block" field types only accepts strings as "add_button_text" schema option!');
    } finally {
        consoleErrorSpy.mockRestore();
    }
});
