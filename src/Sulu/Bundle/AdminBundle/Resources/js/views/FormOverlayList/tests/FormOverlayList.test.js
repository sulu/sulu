/* eslint-disable flowtype/require-valid-file-annotation */
import mockReact from 'react';
import {mount} from 'enzyme';
import {observable} from 'mobx';
import FormOverlayList from '../FormOverlayList';
import List from '../../List';
import Overlay from '../../../components/Overlay';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import Form from '../../../containers/Form';
import ErrorSnackbar from '../ErrorSnackbar';

const React = mockReact;

jest.mock('../../List', () => class ListMock extends mockReact.Component {
    listStore = {};

    render() {
        return <div>list view mock</div>;
    }
});

jest.mock('../../../containers/Form', () => class ListMock extends mockReact.Component {
    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(
    (resourceKey, itemId) => {
        return {
            id: itemId,
        };
    }
));
jest.mock('../../../containers/Form/stores/ResourceFormStore', () => jest.fn(
    (resourceStore) => {
        return {
            id: resourceStore.id,
        };
    }
));

let router;
beforeEach(() => {
    router = {
        attributes: {
            id: 'test-id',
            category: 'category-id',
        },
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test-list-key',
                formKey: 'test-form-key',
                addOverlayTitle: 'app.add_overlay_title',
                editOverlayTitle: 'app.edit_overlay_title',
                resourceKey: 'test-resource-key',
                toolbarActions: ['sulu_admin.add'],
                routerAttributesToListStore: {0: 'category', 'parentId': 'id'},
                routerAttributesToFormStore: {0: 'category', 'parentId': 'id'},
            },
        },
    };
});

test('View should render with closed overlay', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);

    expect(formOverlayList.render()).toMatchSnapshot();
});

test('View should render with opened overlay', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.instance().createFormOverlay(undefined, 'test-list-key');
    formOverlayList.update();

    expect(formOverlayList.render()).toMatchSnapshot();
    expect(formOverlayList.find(Overlay).render()).toMatchSnapshot();
});

test('Should pass correct props to List view', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    const list = formOverlayList.find(List);

    expect(list.props()).toEqual(expect.objectContaining(formOverlayList.props()));
    expect(list.props().onItemAdd).toBeDefined();
    expect(list.props().onItemClick).toBeDefined();
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-add callback', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.find(List).props().onItemAdd();

    expect(ResourceStore).toBeCalledWith('test-resource-key', undefined, {}, {
        category: 'category-id',
        parentId: 'test-id',
    });
    expect(ResourceFormStore).toBeCalledWith(expect.anything(), 'test-form-key', {
        category: 'category-id',
        parentId: 'test-id',
    });
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-click callback', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);

    const locale = observable.box('en');
    formOverlayList.find(List).instance().locale = locale;

    formOverlayList.find(List).props().onItemClick('item-id');

    expect(ResourceStore).toBeCalledWith('test-resource-key', 'item-id', {locale: locale}, {
        category: 'category-id',
        parentId: 'test-id',
    });
    expect(ResourceFormStore).toBeCalledWith(expect.anything(), 'test-form-key', {
        category: 'category-id',
        parentId: 'test-id',
    });
});

test('Should open Overlay with correct props when List fires the item-add callback', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.find(List).props().onItemAdd();

    formOverlayList.update();
    const overlay = formOverlayList.find(Overlay);

    expect(overlay.props()).toEqual(expect.objectContaining({
        confirmDisabled: true,
        confirmLoading: false,
        confirmText: 'sulu_admin.save',
        open: true,
        size: 'small',
        title: 'app.add_overlay_title',
    }));
});

test('Should open Overlay with correct props when List fires the item-click callback', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.find(List).props().onItemClick('item-id');

    formOverlayList.update();
    const overlay = formOverlayList.find(Overlay);

    expect(overlay.props()).toEqual(expect.objectContaining({
        confirmDisabled: true,
        confirmLoading: false,
        confirmText: 'sulu_admin.save',
        open: true,
        size: 'small',
        title: 'app.edit_overlay_title',
    }));
});

test('Should submit Form container when Overlay is confirmed', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.instance().createFormOverlay(undefined, 'test-list-key');
    formOverlayList.update();

    const submitSpy = jest.fn();
    formOverlayList.find(Form).instance().submit = submitSpy;

    formOverlayList.find(Overlay).props().onConfirm();

    expect(submitSpy).toBeCalled();
});

test('Should destroy ResourceFormStore without saving when Overlay is closed', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.instance().createFormOverlay(undefined, 'test-list-key');
    formOverlayList.update();

    const saveSpy = jest.fn();
    const destroySpy = jest.fn();
    formOverlayList.instance().formStore.save = saveSpy;
    formOverlayList.instance().formStore.destroy = destroySpy;

    formOverlayList.find(Overlay).props().onClose();
    formOverlayList.update();

    expect(saveSpy).not.toBeCalled();
    expect(destroySpy).toBeCalled();
    expect(formOverlayList.find(Overlay).exists()).toBeFalsy();
});

test('Should save ResoureFormStore, close overlay and reload List view on submit of Form', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.instance().createFormOverlay(undefined, 'test-list-key');
    formOverlayList.update();

    const savePromise = Promise.resolve();
    const saveSpy = jest.fn(() => savePromise);
    const destroySpy = jest.fn();
    formOverlayList.instance().formStore.save = saveSpy;
    formOverlayList.instance().formStore.destroy = destroySpy;

    const reloadSpy = jest.fn();
    formOverlayList.find(List).instance().listStore.sendRequest = reloadSpy;

    formOverlayList.find(Form).props().onSubmit();

    return savePromise.finally(() => {
        expect(saveSpy).toBeCalled();
        expect(destroySpy).toBeCalled();
        expect(reloadSpy).toBeCalled();

        formOverlayList.update();
        expect(formOverlayList.find(Overlay).exists()).toBeFalsy();
    });
});

test('Should display ErrorSnackbar if an error happens during saving of ResourceFormStore', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.instance().createFormOverlay(undefined, 'test-list-key');
    formOverlayList.update();

    const savePromise = Promise.reject('error');
    const saveSpy = jest.fn(() => savePromise);
    const destroySpy = jest.fn();
    formOverlayList.instance().formStore.save = saveSpy;
    formOverlayList.instance().formStore.destroy = destroySpy;

    const reloadSpy = jest.fn();
    formOverlayList.find(List).instance().listStore.sendRequest = reloadSpy;

    formOverlayList.find(Form).props().onSubmit();

    return new Promise((resolve) => {
        // wait until rejection of savePromise was handled by component with setTimeout
        setTimeout(() => {
            expect(saveSpy).toBeCalled();
            expect(destroySpy).not.toBeCalled();
            expect(reloadSpy).not.toBeCalled();

            formOverlayList.update();
            expect(formOverlayList.find(Overlay).exists()).toBeTruthy();
            expect(formOverlayList.find(ErrorSnackbar).exists).toBeTruthy();
            expect(formOverlayList.find(ErrorSnackbar).props().visible).toBeTruthy();

            resolve();
        });
    });
});

test('Should hide ErrorSnackbar when closeClick callback of ErrorSnackbar is fired', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.instance().createFormOverlay(undefined, 'test-list-key');
    formOverlayList.update();

    formOverlayList.instance().formErrors.push('error 1');
    formOverlayList.update();
    expect(formOverlayList.find(ErrorSnackbar).props().visible).toBeTruthy();

    formOverlayList.find(ErrorSnackbar).props().onCloseClick();
    formOverlayList.update();
    expect(formOverlayList.find(ErrorSnackbar).props().visible).toBeFalsy();
});

test('Should destroy ResourceFormStore when component is unmounted', () => {
    const formOverlayList = mount(<FormOverlayList router={router} />);
    formOverlayList.instance().createFormOverlay(undefined, 'test-list-key');
    formOverlayList.update();

    const destroySpy = jest.fn();
    formOverlayList.instance().formStore.destroy = destroySpy;

    formOverlayList.unmount();
    expect(destroySpy).toBeCalled();
});
