// @flow
import mockReact from 'react';
import {mount} from 'enzyme';
import FormContainer, {ResourceFormStore} from '../../../../containers/Form';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import Form from '../../../../views/Form';
import SaveWithFormDialogToolbarAction from '../../toolbarActions/SaveWithFormDialogToolbarAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../containers/Form/Form', () => class extends mockReact.Component<*> {
    submit = jest.fn();

    render() {
        return null;
    }
});

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => class {
    resourceStore;
    constructor(resourceStore) {
        this.resourceStore = resourceStore;
    }

    get dirty() {
        return this.resourceStore.dirty;
    }

    get saving() {
        return this.resourceStore.saving;
    }
});

jest.mock('../../../../containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({destroy: jest.fn()})),
}));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createSaveWithFormDialogToolbarAction(options: {[string]: any}) {
    const locales = [];
    const resourceStore = new ResourceStore('test');
    const formStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales,
        resourceStore,
        route: router.route,
        router,
    });

    return new SaveWithFormDialogToolbarAction(formStore, form, router, locales, options, resourceStore);
}

test('Return item config with correct disabled, loading, icon, type and value', () => {
    const saveWithFormDialogToolbarAction = createSaveWithFormDialogToolbarAction({condition: 'true', formKey: 'test'});
    saveWithFormDialogToolbarAction.resourceFormStore.resourceStore.saving = false;

    expect(saveWithFormDialogToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        label: 'sulu_admin.save',
        loading: false,
        icon: 'su-save',
        type: 'button',
    }));
});

test('Return item config with enabled button when dirty flag is set', () => {
    const saveWithFormDialogToolbarAction = createSaveWithFormDialogToolbarAction({condition: 'true', formKey: 'test'});
    saveWithFormDialogToolbarAction.resourceFormStore.resourceStore.dirty = true;

    expect(saveWithFormDialogToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
    }));
});

test('Return item config with loading button when saving flag is set', () => {
    const saveWithFormDialogToolbarAction = createSaveWithFormDialogToolbarAction({condition: 'true', formKey: 'test'});
    saveWithFormDialogToolbarAction.resourceFormStore.resourceStore.saving = true;

    expect(saveWithFormDialogToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
    }));
});

test('Throw error if no formKey is passed', () => {
    expect(() => createSaveWithFormDialogToolbarAction({})).toThrow(/"formKey"/);
});

test('Destroy store when being destroyed', () => {
    const saveWithFormDialogToolbarAction = createSaveWithFormDialogToolbarAction({formKey: 'test'});
    saveWithFormDialogToolbarAction.destroy();

    expect(saveWithFormDialogToolbarAction.dialogFormStore.destroy).toBeCalledWith();
});

test('Close dialog when cancel button of dialog is clicked', () => {
    const saveWithFormDialogToolbarAction = createSaveWithFormDialogToolbarAction(
        {condition: 'true', formKey: 'test', title: 'Test'}
    );

    const toolbarItemConfig = saveWithFormDialogToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    let element = mount(saveWithFormDialogToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(saveWithFormDialogToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Dialog').prop('onCancel')();
    element = mount(saveWithFormDialogToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    const dialogForm = saveWithFormDialogToolbarAction.dialogForm;
    if (!dialogForm) {
        throw new Error('The dialogForm should be defined');
    }

    expect(dialogForm.submit).not.toBeCalled();
});

test('Submit form with passed form data dialog when confirm button of dialog is clicked', () => {
    const saveWithFormDialogToolbarAction = createSaveWithFormDialogToolbarAction(
        {condition: 'title == "test1" && __parent.title == "test2"', formKey: 'test', title: 'Test'}
    );

    // $FlowFixMe
    saveWithFormDialogToolbarAction.resourceFormStore.data = {
        title: 'test1',
    };

    saveWithFormDialogToolbarAction.parentResourceStore.data = {
        title: 'test2',
    };

    const toolbarItemConfig = saveWithFormDialogToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    let element = mount(saveWithFormDialogToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(saveWithFormDialogToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    // $FlowFixMe
    saveWithFormDialogToolbarAction.dialogFormStore.data = {test: 'Test'};

    element.find(FormContainer).prop('onSubmit')();
    element = mount(saveWithFormDialogToolbarAction.getNode()).at(0);

    const dialogForm = saveWithFormDialogToolbarAction.dialogForm;
    if (!dialogForm) {
        throw new Error('The dialogForm should be defined');
    }

    expect(saveWithFormDialogToolbarAction.form.submit).toBeCalledWith({test: 'Test'});
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Submit form without form data dialog when condition does not evaluate to true', () => {
    const saveWithFormDialogToolbarAction = createSaveWithFormDialogToolbarAction(
        {condition: 'false', formKey: 'test', title: 'Test'}
    );

    const toolbarItemConfig = saveWithFormDialogToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    clickHandler();

    expect(saveWithFormDialogToolbarAction.form.submit).toBeCalledWith();
});
