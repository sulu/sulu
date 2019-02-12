// @flow
import {observable} from 'mobx';
import MemoryFormStore from '../../stores/MemoryFormStore';

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
    };

    const memoryFormStore = new MemoryFormStore({}, schema);
    expect(memoryFormStore.loading).toEqual(false);

    expect(Object.keys(memoryFormStore.data)).toHaveLength(2);
    expect(memoryFormStore.data).toEqual({
        title: undefined,
        description: undefined,
    });
    memoryFormStore.destroy();
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
    memoryFormStore.destroy();
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
    memoryFormStore.destroy();
});

test('Evaluate all disabledConditions and visibleConditions for schema', () => {
    const schema = {
        item1: {
            type: 'text_line',
        },
        item2: {
            type: 'text_line',
            disabledCondition: 'item1 != "item2"',
            visibleCondition: 'item1 == "item2"',
        },
        section: {
            items: {
                item31: {
                    type: 'text_line',
                },
                item32: {
                    type: 'text_line',
                    disabledCondition: 'item1 != "item32"',
                    visibleCondition: 'item1 == "item32"',
                },
            },
            type: 'section',
            disabledCondition: 'item1 != "section"',
            visibleCondition: 'item1 == "section"',
        },
        block: {
            type: 'block',
            types: {
                text_line: {
                    title: 'item41',
                    form: {
                        item41: {
                            type: 'text_line',
                            disabledCondition: 'item1 != "item41"',
                            visibleCondition: 'item1 == "item41"',
                        },
                        item42: {
                            type: 'text_line',
                        },
                    },
                },
            },
        },
    };

    const memoryFormStore = new MemoryFormStore({}, schema);

    setTimeout(() => {
        const sectionItems1 = memoryFormStore.schema.section.items;
        if (!sectionItems1) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes1 = memoryFormStore.schema.block.types;
        if (!blockTypes1) {
            throw new Error('Block types should be defined!');
        }

        expect(memoryFormStore.schema.item2.disabled).toEqual(true);
        expect(memoryFormStore.schema.item2.visible).toEqual(false);
        expect(sectionItems1.item32.disabled).toEqual(true);
        expect(sectionItems1.item32.visible).toEqual(false);
        expect(memoryFormStore.schema.section.disabled).toEqual(true);
        expect(memoryFormStore.schema.section.visible).toEqual(false);
        expect(blockTypes1.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes1.text_line.form.item41.visible).toEqual(false);

        memoryFormStore.data = observable({item1: 'item2'});
        expect(memoryFormStore.schema.item2.disabled).toEqual(true);
        expect(memoryFormStore.schema.item2.visible).toEqual(false);

        memoryFormStore.finishField('/item1');
        const sectionItems2 = memoryFormStore.schema.section.items;
        if (!sectionItems2) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes2 = memoryFormStore.schema.block.types;
        if (!blockTypes2) {
            throw new Error('Block types should be defined!');
        }

        expect(memoryFormStore.schema.item2.disabled).toEqual(false);
        expect(memoryFormStore.schema.item2.visible).toEqual(true);
        expect(sectionItems2.item32.disabled).toEqual(true);
        expect(sectionItems2.item32.visible).toEqual(false);
        expect(memoryFormStore.schema.section.disabled).toEqual(true);
        expect(memoryFormStore.schema.section.visible).toEqual(false);
        expect(blockTypes2.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes2.text_line.form.item41.visible).toEqual(false);

        memoryFormStore.data = observable({item1: 'item32'});

        memoryFormStore.finishField('/item1');
        const sectionItems3 = memoryFormStore.schema.section.items;
        if (!sectionItems3) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes3 = memoryFormStore.schema.block.types;
        if (!blockTypes3) {
            throw new Error('Block types should be defined!');
        }

        expect(memoryFormStore.schema.item2.disabled).toEqual(true);
        expect(memoryFormStore.schema.item2.visible).toEqual(false);
        expect(sectionItems3.item32.disabled).toEqual(false);
        expect(sectionItems3.item32.visible).toEqual(true);
        expect(memoryFormStore.schema.section.disabled).toEqual(true);
        expect(memoryFormStore.schema.section.visible).toEqual(false);
        expect(blockTypes3.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes3.text_line.form.item41.visible).toEqual(false);

        memoryFormStore.data = observable({item1: 'section'});
        memoryFormStore.finishField('/item1');

        const sectionItems4 = memoryFormStore.schema.section.items;
        if (!sectionItems4) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes4 = memoryFormStore.schema.block.types;
        if (!blockTypes4) {
            throw new Error('Block types should be defined!');
        }

        expect(memoryFormStore.schema.item2.disabled).toEqual(true);
        expect(memoryFormStore.schema.item2.visible).toEqual(false);
        expect(sectionItems4.item32.disabled).toEqual(true);
        expect(sectionItems4.item32.visible).toEqual(false);
        expect(memoryFormStore.schema.section.disabled).toEqual(false);
        expect(memoryFormStore.schema.section.visible).toEqual(true);
        expect(blockTypes4.text_line.form.item41.disabled).toEqual(true);
        expect(blockTypes4.text_line.form.item41.visible).toEqual(false);

        memoryFormStore.data = observable({item1: 'item41'});
        memoryFormStore.finishField('/item1');
        const sectionItems5 = memoryFormStore.schema.section.items;
        if (!sectionItems5) {
            throw new Error('Section items should be defined!');
        }
        const blockTypes5 = memoryFormStore.schema.block.types;
        if (!blockTypes5) {
            throw new Error('Block types should be defined!');
        }

        expect(memoryFormStore.schema.item2.disabled).toEqual(true);
        expect(memoryFormStore.schema.item2.visible).toEqual(false);
        expect(sectionItems5.item32.disabled).toEqual(true);
        expect(sectionItems5.item32.visible).toEqual(false);
        expect(memoryFormStore.schema.section.disabled).toEqual(true);
        expect(memoryFormStore.schema.section.visible).toEqual(false);
        expect(blockTypes5.text_line.form.item41.disabled).toEqual(false);
        expect(blockTypes5.text_line.form.item41.visible).toEqual(true);

        memoryFormStore.destroy();
    }, 0);
});

test('Evaluate disabledConditions and visibleConditions for schema with locale', (done) => {
    const schema = {
        item: {
            type: 'text_line',
            disabledCondition: '__locale == "en"',
            visibleCondition: '__locale == "de"',
        },
    };

    const memoryFormStore = new MemoryFormStore({}, schema, {}, observable.box('en'));

    setTimeout(() => {
        expect(memoryFormStore.schema.item.disabled).toEqual(true);
        expect(memoryFormStore.schema.item.visible).toEqual(false);
        memoryFormStore.destroy();
        done();
    });
});

test('Evaluate disabledConditions and visibleConditions when changing locale', (done) => {
    const schema = {
        item: {
            type: 'text_line',
            disabledCondition: '__locale == "en"',
            visibleCondition: '__locale == "de"',
        },
    };

    const locale = observable.box('en');
    const memoryFormStore = new MemoryFormStore({}, schema, {}, locale);

    setTimeout(() => {
        expect(memoryFormStore.schema.item.disabled).toEqual(true);
        expect(memoryFormStore.schema.item.visible).toEqual(false);

        locale.set('de');
        setTimeout(() => {
            expect(memoryFormStore.schema.item.disabled).toEqual(false);
            expect(memoryFormStore.schema.item.visible).toEqual(true);
            memoryFormStore.destroy();
            done();
        }, 0);
    }, 0);
});

test('Asking for resourceKey should return undefined', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.resourceKey).toBeUndefined();
    memoryFormStore.destroy();
});

test('Asking for resourceKey should return undefined', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.resourceKey).toBeUndefined();
    memoryFormStore.destroy();
});

test('Asking for id should return undefined', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.id).toBeUndefined();
    memoryFormStore.destroy();
});

test('Read dirty flag', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.dirty).toEqual(false);
    memoryFormStore.destroy();
});

test('Set dirty flag', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    memoryFormStore.change('test', 'test');
    expect(memoryFormStore.dirty).toEqual(true);
    memoryFormStore.destroy();
});

test('Loading flag should always be false', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    expect(memoryFormStore.loading).toEqual(false);
    memoryFormStore.destroy();
});

test('Data attribute should return the data', () => {
    const data = observable({
        title: 'Title',
    });

    const memoryFormStore = new MemoryFormStore(data, {});

    expect(memoryFormStore.data).toBe(data);
    memoryFormStore.destroy();
});

test('Destroying the store should call all the disposers', () => {
    const memoryFormStore = new MemoryFormStore({}, {});
    memoryFormStore.updateFieldPathEvaluationsDisposer = jest.fn();

    memoryFormStore.destroy();

    expect(memoryFormStore.updateFieldPathEvaluationsDisposer).toBeCalled();
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
});

test('Validate should return false if errors occured', () => {
    const memoryFormStore = new MemoryFormStore({}, {}, {required: ['title']});

    expect(memoryFormStore.validate()).toEqual(false);
});
