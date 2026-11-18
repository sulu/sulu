// @flow
import mockReact from 'react';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceRequester from '../../../../services/ResourceRequester';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import Form from '../../../../views/Form';
import SaveAndCreateToolbarAction from '../../toolbarActions/SaveAndCreateToolbarAction';

jest.mock('../../../../utils/Translator');

jest.mock('../../../../containers/Form/Form', () => class extends mockReact.Component<*> {
    submit = jest.fn();

    render() {
        return null;
    }
});

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => class {
    resourceStore;
    id = 'content-id';
    locale = {get: () => 'en'};
    constructor(resourceStore) {
        this.resourceStore = resourceStore;
    }

    get data() {
        return {};
    }

    get saving() {
        return this.resourceStore.saving;
    }
});

jest.mock('../../../../containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({data: {schedule: '2026-11-25T10:00:00+00:00'}, destroy: jest.fn()})),
}));

jest.mock('../../../../services/ResourceRequester', () => ({post: jest.fn(() => Promise.resolve({}))}));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.save = jest.fn(() => Promise.resolve({id: 'content-id'}));
    this.showSuccessSnackbar = jest.fn();
}));

const OPTIONS = {
    data: {handlerClass: 'Handler'},
    formKey: 'dialog_form',
    label: 'app.publish_at',
    parameters: {entityClass: 'Entity'},
    resourceKey: 'tasks',
    title: 'app.publish_at',
};

function createAction(options: {[string]: any} = OPTIONS) {
    const locales = [];
    const resourceStore = new ResourceStore('test');
    const formStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({locales, resourceStore, route: router.route, router});

    return new SaveAndCreateToolbarAction(formStore, form, router, locales, options, resourceStore);
}

function getOnClick(saveAndCreateToolbarAction: any) {
    const config = saveAndCreateToolbarAction.getToolbarItemConfig();
    if (!config || !config.onClick) {
        throw new Error('The entry should have an onClick callback.');
    }

    return config.onClick;
}

beforeEach(() => {
    ResourceRequester.post.mockClear();
});

test('Return an item config with the translated label and a button type', () => {
    expect(createAction().getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'app.publish_at',
        type: 'button',
    }));
});

test('Return nothing while the visible condition is not fulfilled', () => {
    const saveAndCreateToolbarAction = createAction({...OPTIONS, visible_condition: 'false'});

    expect(saveAndCreateToolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Return the item config while the visible condition is fulfilled', () => {
    const saveAndCreateToolbarAction = createAction({...OPTIONS, visible_condition: 'true'});

    expect(saveAndCreateToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({type: 'button'}));
});

test('Throw an error if no formKey is passed', () => {
    expect(() => createAction({resourceKey: 'tasks'})).toThrow(/"formKey"/);
});

test('Throw an error if no resourceKey is passed', () => {
    expect(() => createAction({formKey: 'dialog_form'})).toThrow(/"resourceKey"/);
});

test('Open the dialog when the entry is clicked and close it on cancel', () => {
    const saveAndCreateToolbarAction = createAction();

    expect(saveAndCreateToolbarAction.getNode().props.open).toEqual(false);

    getOnClick(saveAndCreateToolbarAction)();
    expect(saveAndCreateToolbarAction.getNode().props.open).toEqual(true);

    saveAndCreateToolbarAction.getNode().props.onCancel();
    expect(saveAndCreateToolbarAction.getNode().props.open).toEqual(false);
});

test('Submit the dialog form when the dialog is confirmed', () => {
    const saveAndCreateToolbarAction = createAction();
    const submit = jest.fn();
    (saveAndCreateToolbarAction: any).dialogForm = {submit};

    saveAndCreateToolbarAction.getNode().props.onConfirm();

    expect(submit).toHaveBeenCalledWith();
});

test('Save the form and create the item with the data of the dialog and the id of the content', () => {
    const saveAndCreateToolbarAction = createAction();

    return saveAndCreateToolbarAction.handleSubmit().then(() => {
        expect(saveAndCreateToolbarAction.form.save).toHaveBeenCalledWith({action: 'draft'});
        expect(ResourceRequester.post).toHaveBeenCalledWith(
            'tasks',
            {handlerClass: 'Handler', schedule: '2026-11-25T10:00:00+00:00'},
            {entityClass: 'Entity', id: 'content-id', locale: 'en'}
        );
        expect(saveAndCreateToolbarAction.form.showSuccessSnackbar).toHaveBeenCalledWith();
    });
});

test('Name the parameters of the content as the options say', () => {
    const saveAndCreateToolbarAction = createAction({...OPTIONS, idParameter: 'entityId', localeParameter: 'lang'});

    return saveAndCreateToolbarAction.handleSubmit().then(() => {
        expect(ResourceRequester.post).toHaveBeenCalledWith(
            'tasks',
            expect.anything(),
            {entityClass: 'Entity', entityId: 'content-id', lang: 'en'}
        );
    });
});

test('Create nothing when the save did not succeed', () => {
    const saveAndCreateToolbarAction = createAction();
    saveAndCreateToolbarAction.form.save.mockReturnValue(Promise.resolve(undefined));

    return saveAndCreateToolbarAction.handleSubmit().then(() => {
        expect(ResourceRequester.post).not.toHaveBeenCalled();
    });
});

test('Destroy the store of the dialog form when being destroyed', () => {
    const saveAndCreateToolbarAction = createAction();
    saveAndCreateToolbarAction.destroy();

    expect(saveAndCreateToolbarAction.dialogFormStore.destroy).toHaveBeenCalledWith();
});
