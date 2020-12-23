// @flow
import {observable} from 'mobx';
import MemoryFormStore from '../../stores/MemoryFormStore';
import conditionDataProviderRegistry from '../../registries/conditionDataProviderRegistry';

beforeEach(() => {
    conditionDataProviderRegistry.clear();
});

test('Create data object for schema', () => {
    const schema = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
        'ext/seo/title': {
            label: 'Description',
            type: 'text_line',
        },
    };

    const memoryFormStore = new MemoryFormStore({}, schema);
    expect(memoryFormStore.loading).toEqual(false);

    expect(memoryFormStore.data).toEqual({
        title: undefined,
        description: undefined,
        ext: {
            seo: {
                title: undefined,
            },
        },
    });
});

test('Create data object for schema while keeping existing data', () => {
    const schema = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    };

    const memoryFormStore = new MemoryFormStore({title: 'Test'}, schema);
    expect(memoryFormStore.loading).toEqual(false);

    expect(Object.keys(memoryFormStore.data)).toHaveLength(2);
    expect(memoryFormStore.data).toEqual({
        title: 'Test',
        description: undefined,
    });
});

test('Create data object for schema with sections', () => {
    const schema = {
        section1: {
            label: 'Section 1',
            type: 'section',
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                },
                section11: {
                    label: 'Section 1.1',
                    type: 'section',
                },
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                },
            },
        },
    };

    const memoryFormStore = new MemoryFormStore({}, schema);

    expect(memoryFormStore.data).toEqual({
        item11: undefined,
        item21: undefined,
    });
});

test('Asking for resourceKey should return undefined', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.resourceKey).toBeUndefined();
});

test('Asking for resourceKey should return undefined', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.resourceKey).toBeUndefined();
});

test('Asking for id should return undefined', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.id).toBeUndefined();
});

test('Read dirty flag', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.dirty).toEqual(false);
});

test('Set dirty flag', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    memoryFormStore.change('test', 'test');
    expect(memoryFormStore.dirty).toEqual(true);
});

test('Set nested value', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    memoryFormStore.change('test1/test2', 'test');
    expect(memoryFormStore.data).toEqual({test1: {test2: 'test'}});
});

test('Set multiple values', () => {
    const memoryFormStore = new MemoryFormStore({}, {});

    memoryFormStore.setMultiple({key1: 'value1', key2: 'value2'});
    memoryFormStore.setMultiple({key2: 'newValue2', key3: 'value3'});

    expect(memoryFormStore.data).toEqual({key1: 'value1', key2: 'newValue2', key3: 'value3'});
});

test('Loading flag should always be false', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.loading).toEqual(false);
});

test('Data attribute should return the data', () => {
    const data = observable({
        title: 'Title',
    });

    const memoryFormStore = new MemoryFormStore(data, {});

    expect(memoryFormStore.data).toBe(data);
});

test('Should return value for property path', () => {
    const memoryFormStore = new MemoryFormStore(observable({test: 'value'}), {});

    expect(memoryFormStore.getValueByPath('/test')).toEqual('value');
});

test('Return all the values for a given tag', () => {
    const data = observable({
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
    });

    const schema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_area',
        },
        flag: {
            type: 'checkbox',
            tags: [
                {name: 'sulu.other'},
            ],
        },
    };

    const memoryFormStore = new MemoryFormStore(data, schema);

    expect(memoryFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Value 2']);
});

test('Return all the values for a given tag sorted by priority', () => {
    const data = observable({
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
    });

    const schema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part', priority: 10},
            ],
            type: 'text_line',
        },
        description: {
            tags: [
                {name: 'sulu.resource_locator_part', priority: 100},
            ],
            type: 'text_area',
        },
        flag: {
            type: 'checkbox',
        },
    };

    const memoryFormStore = new MemoryFormStore(data, schema);

    expect(memoryFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 2', 'Value 1']);
});

test('Return all the values for a given tag within sections', () => {
    const data = observable({
        title: 'Value 1',
        description: 'Value 2',
        flag: true,
        article: 'Value 3',
    });

    const schema = {
        highlight: {
            items: {
                title: {
                    tags: [
                        {name: 'sulu.resource_locator_part'},
                    ],
                    type: 'text_line',
                },
                description: {
                    tags: [
                        {name: 'sulu.resource_locator_part'},
                    ],
                    type: 'text_area',
                },
                flag: {
                    type: 'checkbox',
                },
            },
            type: 'section',
        },
        article: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_area',
        },
    };

    const memoryFormStore = new MemoryFormStore(data, schema);

    expect(memoryFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Value 2', 'Value 3']);
});

test('Return all the values for a given tag with empty blocks', () => {
    const data = observable({
        title: 'Value 1',
        description: 'Value 2',
    });

    const schema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            type: 'text_area',
        },
        block: {
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
            },
        },
    };

    const memoryFormStore = new MemoryFormStore(data, schema);

    expect(memoryFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1']);
});

test('Return all the values for a given tag within blocks', () => {
    const data = observable({
        title: 'Value 1',
        description: 'Value 2',
        block: [
            {type: 'default', text: 'Block 1', description: 'Block Description 1'},
            {type: 'default', text: 'Block 2', description: 'Block Description 2'},
            {type: 'other', text: 'Block 3', description: 'Block Description 2'},
        ],
        image_map: {
            hotspots: [
                {type: 'default', text: 'Image Map', description: 'Image Map Description 1'},
            ],
        },
    });

    const schema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            type: 'text_area',
        },
        block: {
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
                other: {
                    form: {
                        text: {
                            type: 'text_line',
                        },
                    },
                    title: 'Other',
                },
            },
        },
        image_map: {
            type: 'image_map',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
                other: {
                    form: {
                        text: {
                            type: 'text_line',
                        },
                    },
                    title: 'Other',
                },
            },
        },
    };

    const memoryFormStore = new MemoryFormStore(data, schema);

    expect(memoryFormStore.getValuesByTag('sulu.resource_locator_part')).toEqual(['Value 1', 'Block 1', 'Block 2']);
});

test('Return SchemaEntry for given schemaPath', () => {
    const schema = {
        title: {
            tags: [
                {name: 'sulu.resource_locator_part'},
            ],
            type: 'text_line',
        },
        description: {
            type: 'text_area',
        },
        block: {
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            tags: [
                                {name: 'sulu.resource_locator_part'},
                            ],
                            type: 'text_line',
                        },
                        description: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
            },
        },
    };

    const memoryFormStore = new MemoryFormStore({}, schema);

    expect(memoryFormStore.getSchemaEntryByPath('/block/types/default/form/text')).toEqual({
        tags: [
            {name: 'sulu.resource_locator_part'},
        ],
        type: 'text_line',
    });
});

test('Remember fields being finished as modified fields', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    memoryFormStore.finishField('/block/0/text');
    memoryFormStore.finishField('/block/0/text');
    memoryFormStore.finishField('/block/1/text');

    expect(memoryFormStore.isFieldModified('/block/0/text')).toEqual(true);
    expect(memoryFormStore.isFieldModified('/block/1/text')).toEqual(true);
    expect(memoryFormStore.isFieldModified('/block/2/text')).toEqual(false);
});

test('Validate should return true if no errors occured', () => {
    const memoryFormStore = new MemoryFormStore({title: 'Test'}, {}, {required: ['title']});

    expect(memoryFormStore.validate()).toEqual(true);
    expect(memoryFormStore.hasErrors).toEqual(false);
});

test('Validate should return false if errors occured', () => {
    const memoryFormStore = new MemoryFormStore({}, {}, {required: ['title']});

    expect(memoryFormStore.validate()).toEqual(false);
    expect(memoryFormStore.hasErrors).toEqual(true);
});

test('Forbidden flag should always be set to false', () => {
    const memoryFormStore = new MemoryFormStore({}, {}, {required: ['title']});

    expect(memoryFormStore.forbidden).toEqual(false);
});
