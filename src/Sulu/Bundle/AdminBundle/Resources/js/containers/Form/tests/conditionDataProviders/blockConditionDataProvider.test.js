// @flow
import MemoryFormStore from '../../stores/MemoryFormStore';
import FormInspector from '../../FormInspector';
import blockConditionDataProvider from '../../conditionDataProviders/blockConditionDataProvider';

test('Return nothing if the form has no owning block', () => {
    const formInspector = new FormInspector(new MemoryFormStore({hidden: false}, {}));

    expect(blockConditionDataProvider({hidden: false}, '/hidden', formInspector)).toEqual({});
});

test('Return the owning block from the form options', () => {
    const block = {type: 'image'};
    const formStore = new MemoryFormStore({hidden: false}, {}, undefined, undefined, undefined, {__block: block});
    const formInspector = new FormInspector(formStore);

    expect(blockConditionDataProvider({hidden: false}, '/hidden', formInspector)).toEqual({__block: block});
});

test('Return the owning block independent of the depth of the property', () => {
    const block = {type: 'image'};
    const data = {schedules: [{start: 'now'}]};
    const formStore = new MemoryFormStore(data, {}, undefined, undefined, undefined, {__block: block});
    const formInspector = new FormInspector(formStore);

    expect(blockConditionDataProvider(data, '/schedules/0/start', formInspector)).toEqual({__block: block});
    expect(blockConditionDataProvider(data, undefined, formInspector)).toEqual({__block: block});
});
