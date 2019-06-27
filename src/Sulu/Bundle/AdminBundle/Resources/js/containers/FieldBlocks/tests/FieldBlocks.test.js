// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import Router from '../../../services/Router';
import fieldTypeDefaultProps from '../../../utils/TestHelper/fieldTypeDefaultProps';
import FieldBlocks from '../FieldBlocks';
import FormInspector from '../../Form/FormInspector';
import ResourceFormStore from '../../Form/stores/ResourceFormStore';
import ResourceStore from '../../../stores/ResourceStore';
import blockPreviewTransformerRegistry from '../registries/BlockPreviewTransformerRegistry';

jest.mock('../../../services/Router', () => jest.fn());
jest.mock('../../Form/FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
    this.getSchemaEntryByPath = jest.fn();
}));
jest.mock('../../Form/stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('../../Form/registries/FieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return function TextLine({error, value}) {
                    return <input className={error && error.keyword} defaultValue={value} type="text" />;
                };
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../registries/BlockPreviewTransformerRegistry', () => ({
    has: jest.fn(),
    get: jest.fn(),
    blockPreviewTransformerKeysByPriority: [],
}));

beforeEach(() => {
    blockPreviewTransformerRegistry.has.mockClear();
    blockPreviewTransformerRegistry.get.mockClear();
    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [];
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
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                    visible: true,
                },
                text2: {
                    label: 'Text 2',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                    visible: true,
                },
                something: {
                    label: 'Something',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_area',
                    visible: true,
                },
                nothing: {
                    label: 'Nothing',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text1: 'Test 1',
            text2: undefined,
            something: 'Test 3',
            type: 'default',
        },
        {
            text1: 'Test 4',
            text2: undefined,
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

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.render()).toMatchSnapshot();
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
                    visible: true,
                },
                text1: {
                    label: 'Text 1',
                    type: 'text_line',
                    visible: true,
                },
                text2: {
                    label: 'Text 2',
                    type: 'media_selection',
                    visible: true,
                },
                something: {
                    label: 'Text 3',
                    type: 'text_editor',
                    visible: true,
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

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.render()).toMatchSnapshot();
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
                    visible: true,
                },
                text2: {
                    label: 'Text 2',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                    visible: true,
                },
                something: {
                    label: 'Text 3',
                    tags: [
                        {name: 'sulu.block_preview', priority: 100},
                    ],
                    type: 'text_line',
                    visible: true,
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

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.render()).toMatchSnapshot();
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
                    visible: true,
                },
                text2: {
                    label: 'Text 2',
                    type: 'text_line',
                    visible: true,
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

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');

    expect(fieldBlocks.render()).toMatchSnapshot();
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
                    visible: true,
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

    const fieldBlocks = mount(
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

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(2).simulate('click');

    expect(fieldBlocks.render()).toMatchSnapshot();
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
                    visible: true,
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

    const fieldBlocks = mount(
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

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(2).simulate('click');

    fieldBlocks.find('Block').at(0).find('Field').at(0).prop('onFinish')('text');
    fieldBlocks.find('Block').at(1).find('Field').at(0).prop('onFinish')('text');

    expect(fieldBlocks.render()).toMatchSnapshot();
});

test('Should correctly pass props to the BlockCollection', () => {
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
    const value = [];
    const changeSpy = jest.fn();

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={1}
            onChange={changeSpy}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.find('BlockCollection').props()).toEqual(expect.objectContaining({
        disabled: true,
        maxOccurs: 2,
        minOccurs: 1,
        onChange: changeSpy,
        types: {
            default: 'Default',
        },
        value,
    }));
});

test('Should pass correct schemaPath and router to FieldRenderer', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const router = new Router();

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath=""
            defaultType="editor"
            formInspector={formInspector}
            router={router}
            schemaPath=""
            types={types}
            value={[{type: 'default'}, {type: 'default'}]}
        />
    );

    fieldBlocks.find('SortableBlockList').prop('onExpand')(0);
    fieldBlocks.find('SortableBlockList').prop('onExpand')(1);
    fieldBlocks.update();

    expect(fieldBlocks.find('FieldRenderer').at(0).prop('schemaPath')).toEqual('/types/default/form');
    expect(fieldBlocks.find('FieldRenderer').at(0).prop('router')).toEqual(router);
    expect(fieldBlocks.find('FieldRenderer').at(1).prop('schemaPath')).toEqual('/types/default/form');
    expect(fieldBlocks.find('FieldRenderer').at(1).prop('router')).toEqual(router);
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
                    visible: true,
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const finishSpy = jest.fn();
    const fieldBlocks = mount(
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

    fieldBlocks.find('Block').simulate('click');
    fieldBlocks.find('FieldRenderer').prop('onFieldFinish')();

    expect(finishSpy).toBeCalledWith();
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
                    visible: true,
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const finishSpy = jest.fn();
    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            onFinish={finishSpy}
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('BlockCollection').prop('onSortEnd')(0, 2);

    expect(finishSpy).toBeCalledWith();
});

test('Throw error if no default type are passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    )).toThrow('The "block" field type needs a defaultType!');
});

test('Throw error if no types are passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
        />
    )).toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if empty type array is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            value={[]}
        />
    )).toThrow('The "block" field type needs at least one type to be configured!');
});
