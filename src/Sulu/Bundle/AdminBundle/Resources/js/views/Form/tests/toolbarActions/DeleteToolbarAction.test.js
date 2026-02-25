// @flow
import {render, waitFor} from '@testing-library/react';
import {observable} from 'mobx';
import log from 'loglevel';
import jexl from 'jexl';
import DeleteToolbarAction from '../../toolbarActions/DeleteToolbarAction';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import Dialog from '../../../../components/Dialog';
import DeleteReferencedResourceDialog from '../../../../containers/DeleteReferencedResourceDialog';
import DeleteDependantResourcesDialog from '../../../../containers/DeleteDependantResourcesDialog';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.observableOptions = observableOptions;
}));

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get id() {
            return this.resourceStore.id;
        }

        get data() {
            return this.resourceStore.data;
        }

        get locale() {
            return this.resourceStore.observableOptions.locale;
        }

        delete = jest.fn();
    }
));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.attributes = {};
    this.restore = jest.fn();
    this.route = {
        options: {},
    };
}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

jest.mock('../../../../components/Dialog', () => jest.fn(() => null));
jest.mock('../../../../containers/DeleteReferencedResourceDialog', () => jest.fn(() => null));
jest.mock('../../../../containers/DeleteDependantResourcesDialog', () => jest.fn(() => null));

jexl.addTransform('length', (value: Array<*>) => value.length);

function createDeleteToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test', undefined, {locale: observable.box('en')});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });
    return new DeleteToolbarAction(resourceFormStore, form, router, [], options, resourceStore);
}

function getNodeState(deleteToolbarAction: DeleteToolbarAction): any {
    const dialogMock: any = Dialog;
    const deleteReferencedResourceDialogMock: any = DeleteReferencedResourceDialog;
    const deleteDependantResourcesDialogMock: any = DeleteDependantResourcesDialog;

    const dialogCallsBefore = dialogMock.mock.calls.length;
    const deleteReferencedResourceDialogCallsBefore = deleteReferencedResourceDialogMock.mock.calls.length;
    const deleteDependantResourcesDialogCallsBefore = deleteDependantResourcesDialogMock.mock.calls.length;

    const {unmount} = render(deleteToolbarAction.getNode());
    unmount();

    const dialogProps = dialogMock.mock.calls.length > dialogCallsBefore
        ? getLatestMockProps(dialogMock)
        : {};
    const deleteReferencedResourceDialogProps = deleteReferencedResourceDialogMock.mock.calls.length >
        deleteReferencedResourceDialogCallsBefore
        ? getLatestMockProps(deleteReferencedResourceDialogMock)
        : undefined;
    const deleteDependantResourcesDialogProps = deleteDependantResourcesDialogMock.mock.calls.length >
        deleteDependantResourcesDialogCallsBefore
        ? getLatestMockProps(deleteDependantResourcesDialogMock)
        : undefined;

    return {
        deleteDependantResourcesDialogProps,
        deleteReferencedResourceDialogProps,
        dialogProps,
    };
}

function getDialogProps(nodeState: any): any {
    return nodeState.dialogProps;
}

function hasDeleteReferencedResourceDialog(nodeState: any): boolean {
    return !!nodeState.deleteReferencedResourceDialogProps;
}

function hasDeleteDependantResourcesDialog(nodeState: any): boolean {
    return !!nodeState.deleteDependantResourcesDialogProps;
}

function getDeleteReferencedResourceTitles(nodeState: any): Array<string> {
    return nodeState.deleteReferencedResourceDialogProps?.referencingResourcesData?.referencingResources
        .map((resource) => resource.title) || [];
}

function clickPrimaryButton(nodeState: any): void {
    if (nodeState.deleteReferencedResourceDialogProps) {
        if (nodeState.deleteReferencedResourceDialogProps.allowDeletion) {
            nodeState.deleteReferencedResourceDialogProps.onConfirm();
        } else {
            nodeState.deleteReferencedResourceDialogProps.onCancel();
        }
        return;
    }

    nodeState.dialogProps.onConfirm();
}

function clickSecondaryButton(nodeState: any): void {
    nodeState.dialogProps.onCancel();
}

function finishDeleteDependantResourcesDialog(nodeState: any): void {
    if (!nodeState.deleteDependantResourcesDialogProps) {
        throw new Error('DeleteDependantResourcesDialog should exist');
    }

    nodeState.deleteDependantResourcesDialogProps.onFinish();
}

function cancelDeleteDependantResourcesDialog(nodeState: any): void {
    if (!nodeState.deleteDependantResourcesDialogProps) {
        throw new Error('DeleteDependantResourcesDialog should exist');
    }

    nodeState.deleteDependantResourcesDialogProps.onCancel();
}

test('Return item config with correct disabled, loading, icon, type and value and return closed dialog', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 5;

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        type: 'button',
    }));

    const element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.cancel',
        children: 'sulu_admin.delete_warning_text',
        confirmText: 'sulu_admin.ok',
        open: false,
        title: 'sulu_admin.delete_warning_title',
    }));
    expect(hasDeleteReferencedResourceDialog(element)).toBe(false);
});

test('Return item config with correct translations for deleteLocale', () => {
    const deleteToolbarAction = createDeleteToolbarAction({delete_locale: true});
    deleteToolbarAction.resourceFormStore.resourceStore.id = 5;

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete_locale',
        type: 'button',
    }));

    const element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.cancel',
        children: 'sulu_admin.delete_locale_warning_text',
        confirmText: 'sulu_admin.ok',
        open: false,
        title: 'sulu_admin.delete_locale_warning_title',
    }));
    expect(hasDeleteReferencedResourceDialog(element)).toBe(false);
});

test('Return item config with disabled button if an add form is opened', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return item config with enabled button if more than one contentLocale and an id is available', () => {
    const deleteToolbarAction = createDeleteToolbarAction({delete_locale: true});
    deleteToolbarAction.resourceFormStore.resourceStore.id = '123-123-123';
    deleteToolbarAction.resourceFormStore.resourceStore.data = {contentLocales: ['de', 'en']};

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
    }));
});

test('Return item config with disabled button if only one contentLocale is available', () => {
    const deleteToolbarAction = createDeleteToolbarAction({delete_locale: true});
    deleteToolbarAction.resourceFormStore.resourceStore.id = '123-123-123';
    deleteToolbarAction.resourceFormStore.resourceStore.data = {contentLocales: ['de']};

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return item config with disabled button if there is no id', () => {
    const deleteToolbarAction = createDeleteToolbarAction({delete_locale: true});
    deleteToolbarAction.resourceFormStore.resourceStore.id = null;
    deleteToolbarAction.resourceFormStore.resourceStore.data = {contentLocales: ['de']};

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return empty item config when deprecated display_condition is not met', () => {
    const deleteToolbarAction = createDeleteToolbarAction({display_condition: 'url == "/"'});

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(undefined);
    expect(log.warn).toBeCalledWith(expect.stringContaining('The "display_condition" option is deprecated'));
});

test('Return empty item config when passed visible_condition is not met', () => {
    const deleteToolbarAction = createDeleteToolbarAction({visible_condition: 'url == "/"'});

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(undefined);
    expect(log.warn).not.toBeCalled();
});

test('Return item config when passed visible_condition is met', () => {
    const deleteToolbarAction = createDeleteToolbarAction({visible_condition: 'url == "/"'});
    deleteToolbarAction.resourceFormStore.resourceStore.data = {url: '/'};

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({label: 'sulu_admin.delete'}));
});

test('Throw error when "delete_locale" option is not a boolean', () => {
    const resourceStore = new ResourceStore('test', undefined, {locale: observable.box('en')});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    expect(() => {
        return new DeleteToolbarAction(resourceFormStore, form, router, [], {delete_locale: 'test123'}, resourceStore);
    }).toThrow('The "delete_locale" option must be a boolean, but received string!');
});

test('Open dialog on toolbar item click', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    const element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));
});

test('Close dialog on cancel click', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickSecondaryButton(element);
    element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Call delete when dialog is confirmed', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const deletePromise = Promise.resolve();
    deleteToolbarAction.resourceFormStore.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: false});

    return deletePromise.then(() => {
        element = getNodeState(deleteToolbarAction);
        expect(deleteToolbarAction.router.restore).toBeCalledWith('sulu_test.list', {locale: 'en'});
        expect(getDialogProps(element)).toEqual(expect.objectContaining({
            open: false,
        }));
    });
});

test('Call delete when dialog is confirmed with deleteLocale', () => {
    const deleteToolbarAction = createDeleteToolbarAction({delete_locale: true});
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const deletePromise = Promise.resolve();
    deleteToolbarAction.resourceFormStore.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: true});

    return deletePromise.then(() => {
        element = getNodeState(deleteToolbarAction);
        expect(deleteToolbarAction.router.restore).toBeCalledWith('sulu_test.list', {locale: 'en'});
        expect(getDialogProps(element)).toEqual(expect.objectContaining({
            open: false,
        }));
    });
});

test('Call delete when dialog is confirmed with router_attributes_to_back_view option as array', () => {
    const deleteToolbarAction = createDeleteToolbarAction({router_attributes_to_back_view: ['webspace']});
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.attributes.webspace = 'example';
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const deletePromise = Promise.resolve();
    deleteToolbarAction.resourceFormStore.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: false});

    return deletePromise.then(() => {
        element = getNodeState(deleteToolbarAction);
        expect(deleteToolbarAction.router.restore)
            .toBeCalledWith('sulu_test.list', {locale: 'en', webspace: 'example'});
        expect(getDialogProps(element)).toEqual(expect.objectContaining({
            open: false,
        }));
    });
});

test('Call delete when dialog is confirmed with router_attributes_to_back_view option as object', () => {
    const deleteToolbarAction = createDeleteToolbarAction({router_attributes_to_back_view: {webspaceKey: 'webspace'}});
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.attributes.webspaceKey = 'example';
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const deletePromise = Promise.resolve();
    deleteToolbarAction.resourceFormStore.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: false});

    return deletePromise.then(() => {
        element = getNodeState(deleteToolbarAction);
        expect(deleteToolbarAction.router.restore)
            .toBeCalledWith('sulu_test.list', {locale: 'en', webspace: 'example'});
        expect(getDialogProps(element)).toEqual(expect.objectContaining({
            open: false,
        }));
    });
});

test('Call delete with force when dialog is confirmed twice', async() => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const jsonDeletePromise = {
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    };
    const deletePromise = Promise.reject({
        json: jest.fn().mockResolvedValue(jsonDeletePromise),
        status: 409,
    });
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: false});

    await waitFor(() => {
        expect(hasDeleteReferencedResourceDialog(getNodeState(deleteToolbarAction))).toBe(true);
    });
    element = getNodeState(deleteToolbarAction);
    expect(deleteToolbarAction.router.restore).toBeCalledTimes(0);
    expect(getDialogProps(element).open).toEqual(false);
    expect(getDeleteReferencedResourceTitles(element)).toHaveLength(2);
    expect(getDeleteReferencedResourceTitles(element)[0]).toEqual('Item 1');
    expect(getDeleteReferencedResourceTitles(element)[1]).toEqual('Item 2');

    const nextDeletePromise = Promise.resolve({});
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(nextDeletePromise);

    clickPrimaryButton(element);
    await nextDeletePromise;

    await waitFor(() => {
        expect(deleteToolbarAction.router.restore).toBeCalledWith('sulu_test.list', {locale: 'en'});
    });
    expect(getDialogProps(getNodeState(deleteToolbarAction))).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Call delete with force and deleteLocale when dialog is confirmed twice', async() => {
    const deleteToolbarAction = createDeleteToolbarAction({delete_locale: true});
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const jsonDeletePromise = {
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    };
    const deletePromise = Promise.reject({
        json: jest.fn().mockResolvedValue(jsonDeletePromise),
        status: 409,
    });
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: true});

    await waitFor(() => {
        expect(hasDeleteReferencedResourceDialog(getNodeState(deleteToolbarAction))).toBe(true);
    });
    element = getNodeState(deleteToolbarAction);
    expect(deleteToolbarAction.router.restore).toBeCalledTimes(0);
    expect(getDialogProps(element).open).toEqual(false);
    expect(getDeleteReferencedResourceTitles(element)).toHaveLength(2);
    expect(getDeleteReferencedResourceTitles(element)[0]).toEqual('Item 1');
    expect(getDeleteReferencedResourceTitles(element)[1]).toEqual('Item 2');

    const nextDeletePromise = Promise.resolve({});
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(nextDeletePromise);

    clickPrimaryButton(element);
    await nextDeletePromise;

    await waitFor(() => {
        expect(deleteToolbarAction.router.restore).toBeCalledWith('sulu_test.list', {locale: 'en'});
    });
    expect(getDialogProps(getNodeState(deleteToolbarAction))).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Cancel delete conflict occured with the allowConflictDeletion option set to false', async() => {
    const deleteToolbarAction = createDeleteToolbarAction({allow_conflict_deletion: false});
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const jsonDeletePromise = {
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    };
    const deletePromise = Promise.reject({
        json: jest.fn().mockResolvedValue(jsonDeletePromise),
        status: 409,
    });
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: false});

    await waitFor(() => {
        expect(hasDeleteReferencedResourceDialog(getNodeState(deleteToolbarAction))).toBe(true);
    });
    element = getNodeState(deleteToolbarAction);
    expect(deleteToolbarAction.router.restore).toBeCalledTimes(0);
    expect(getDialogProps(element).open).toEqual(false);
    expect(getDeleteReferencedResourceTitles(element)).toHaveLength(2);
    expect(getDeleteReferencedResourceTitles(element)[0]).toEqual('Item 1');
    expect(getDeleteReferencedResourceTitles(element)[1]).toEqual('Item 2');

    const nextDeletePromise = Promise.resolve({});
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(nextDeletePromise);

    clickPrimaryButton(element);
    await nextDeletePromise;

    await waitFor(() => {
        expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledTimes(1);
    });
    expect(deleteToolbarAction.router.restore).not.toBeCalled();
    expect(getDialogProps(getNodeState(deleteToolbarAction))).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Call delete when DeleteDependantResourcesDialog is finished', async() => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const jsonDeletePromise = {
        code: 1105,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        dependantResourceBatches: [
            {id: 7, resourceKey: 'pages'},
            {id: 8, resourceKey: 'pages'},
        ],
        dependantResourcesCount: 2,
    };
    const deletePromise = Promise.reject({
        json: jest.fn().mockResolvedValue(jsonDeletePromise),
        status: 409,
    });
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: false});

    await waitFor(() => {
        expect(hasDeleteDependantResourcesDialog(getNodeState(deleteToolbarAction))).toBe(true);
    });
    element = getNodeState(deleteToolbarAction);
    expect(deleteToolbarAction.router.restore).toBeCalledTimes(0);
    expect(getDialogProps(element).open).toEqual(false);

    const nextDeletePromise = Promise.resolve({});
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(nextDeletePromise);

    finishDeleteDependantResourcesDialog(element);
    await nextDeletePromise;

    await waitFor(() => {
        expect(deleteToolbarAction.router.restore).toBeCalledWith('sulu_test.list', {locale: 'en'});
    });
    expect(getDialogProps(getNodeState(deleteToolbarAction))).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Do not call delete when DeleteDependantResourcesDialog is cancelled', async() => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backView = 'sulu_test.list';

    const jsonDeletePromise = {
        code: 1105,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        dependantResourceBatches: [
            {id: 7, resourceKey: 'pages'},
            {id: 8, resourceKey: 'pages'},
        ],
        dependantResourcesCount: 2,
    };
    const deletePromise = Promise.reject({
        json: jest.fn().mockResolvedValue(jsonDeletePromise),
        status: 409,
    });
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = getNodeState(deleteToolbarAction);
    expect(getDialogProps(element)).toEqual(expect.objectContaining({
        open: true,
    }));

    clickPrimaryButton(element);
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith({deleteLocale: false});

    await waitFor(() => {
        expect(hasDeleteDependantResourcesDialog(getNodeState(deleteToolbarAction))).toBe(true);
    });
    element = getNodeState(deleteToolbarAction);
    expect(deleteToolbarAction.router.restore).toBeCalledTimes(0);
    expect(getDialogProps(element).open).toEqual(false);

    const nextDeletePromise = Promise.resolve({});
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(nextDeletePromise);

    cancelDeleteDependantResourcesDialog(element);
    await nextDeletePromise;

    await waitFor(() => {
        expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledTimes(1);
    });
    expect(deleteToolbarAction.router.restore).not.toBeCalled();
    expect(getDialogProps(getNodeState(deleteToolbarAction))).toEqual(expect.objectContaining({
        open: false,
    }));
});
