// @flow
import mockReact from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import FormOverlayList from '../FormOverlayList';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import FormOverlay from '../../../containers/FormOverlay';
import Router, {Route} from '../../../services/Router';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

const React = mockReact;
const mockListPropsCalls: Array<any> = [];
const mockListReloadFunctions: Array<any> = [];

jest.mock('../../List', () => {
    const React = require('react');
    const ListMock = React.forwardRef(function ListMock(props, ref) {
        const reload = jest.fn();
        React.useImperativeHandle(ref, () => ({reload}));

        mockListPropsCalls.push(props);
        mockListReloadFunctions.push(reload);

        return <div>list view mock</div>;
    });

    return ListMock;
});

jest.mock('../../../containers/Form/Form', () => class FormMock extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('../../../containers/FormOverlay', () => jest.fn(function FormOverlayMock({open}) {
    if (!open) {
        return null;
    }

    return <div>form overlay mock</div>;
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(
    (resourceKey, itemId) => {
        return {
            id: itemId,
        };
    }
));

jest.mock('../../../containers/Form/stores/ResourceFormStore', () => jest.fn(
    (resourceStore, formKey, options, metadataOptions) => {
        return {
            destroy: jest.fn(),
            dirty: false,
            id: resourceStore.id,
            metadataOptions,
        };
    }
));

function getLatestListProps() {
    return mockListPropsCalls[mockListPropsCalls.length - 1];
}

function getLatestListReload() {
    return mockListReloadFunctions[mockListReloadFunctions.length - 1];
}

function getLatestOverlayProps() {
    return getLatestMockProps((FormOverlay: any));
}

function getLatestFormStore() {
    const resourceFormStoreMock: any = ResourceFormStore;
    return resourceFormStoreMock.mock.results[resourceFormStoreMock.mock.results.length - 1].value;
}

beforeEach(() => {
    jest.clearAllMocks();
    mockListPropsCalls.splice(0, mockListPropsCalls.length);
    mockListReloadFunctions.splice(0, mockListReloadFunctions.length);
});

test('View should render with closed overlay', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {},
        },
    }: any);

    const {asFragment} = render(<FormOverlayList route={route} router={router} />);

    expect(asFragment()).toMatchSnapshot();
});

test('View should render with opened overlay', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                addOverlayTitle: 'app.add_overlay_title',
                formKey: 'test-form-key',
            },
        },
    }: any);

    const {asFragment} = render(<FormOverlayList route={route} router={router} />);

    act(() => {
        getLatestListProps().onItemAdd();
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Should pass correct props to List view', () => {
    const route: Route = ({}: any);
    const router: Router = ({
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
                overlaySize: 'large',
                resourceKey: 'test-resource-key',
                toolbarActions: ['sulu_admin.add'],
                routerAttributesToListRequest: {'0': 'category', 'id': 'parentId'},
                routerAttributesToFormRequest: {'0': 'category', 'id': 'parentId'},
            },
        },
    }: any);

    render(<FormOverlayList route={route} router={router} />);
    const listProps = getLatestListProps();

    expect(listProps).toEqual(expect.objectContaining({route, router}));
    expect(listProps.locale).toBeDefined();
    expect(listProps.onItemAdd).toBeDefined();
    expect(listProps.onItemClick).toBeDefined();
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-add callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        attributes: {
            id: 'test-id',
            category: 'category-id',
        },
        route: {
            options: {
                formKey: 'test-form-key',
                resourceKey: 'test-resource-key',
                routerAttributesToFormRequest: {'0': 'category', 'id': 'parentId'},
                resourceStorePropertiesToFormRequest: {'0': 'webspace', 'dimension': 'dimensionId'},
            },
        },
    }: any);

    const testResourceStore = new ResourceStore('test');
    testResourceStore.data = {
        webspace: 'test-webspace',
        dimension: 'test-dimension',
    };

    render(<FormOverlayList resourceStore={testResourceStore} route={route} router={router} />);

    act(() => {
        getLatestListProps().onItemAdd();
    });

    expect(ResourceStore).toHaveBeenLastCalledWith('test-resource-key', undefined, {}, {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    });
    expect(ResourceFormStore).toHaveBeenLastCalledWith(expect.anything(), 'test-form-key', {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    }, {});
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-click callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        attributes: {
            id: 'test-id',
            category: 'category-id',
        },
        route: {
            options: {
                formKey: 'test-form-key',
                resourceKey: 'test-resource-key',
                routerAttributesToFormRequest: {'0': 'category', 'id': 'parentId'},
                resourceStorePropertiesToFormRequest: {'0': 'webspace', 'dimension': 'dimensionId'},
            },
        },
    }: any);

    const testResourceStore = new ResourceStore('test');
    testResourceStore.data = {
        webspace: 'test-webspace',
        dimension: 'test-dimension',
    };

    render(<FormOverlayList resourceStore={testResourceStore} route={route} router={router} />);

    const locale = observable.box('en');
    act(() => {
        getLatestListProps().locale.set(locale.get());
        getLatestListProps().onItemClick('item-id');
    });

    expect(ResourceStore).toHaveBeenLastCalledWith(
        'test-resource-key',
        'item-id',
        {locale: getLatestListProps().locale},
        {
            category: 'category-id',
            parentId: 'test-id',
            webspace: 'test-webspace',
            dimensionId: 'test-dimension',
        }
    );
    expect(ResourceFormStore).toHaveBeenLastCalledWith(expect.anything(), 'test-form-key', {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    }, {});
});

test('Should construct ResourceFormStore with correct metadataOptions on item-add callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        attributes: {
            id: 'test-id',
            webspace: 'webspace-attribute-value',
            template: 'template-attribute-value',
        },
        route: {
            options: {
                formKey: 'test-form-key',
                resourceKey: 'test-resource-key',
                metadataRequestParameters: {'staticParam': 'staticValue'},
                routerAttributesToFormMetadata: {'0': 'webspace', 'template': 'pageTemplate'},
            },
        },
    }: any);

    render(<FormOverlayList route={route} router={router} />);

    act(() => {
        getLatestListProps().locale.set('en');
        getLatestListProps().onItemAdd();
    });

    expect(ResourceFormStore).toHaveBeenLastCalledWith(expect.anything(), 'test-form-key', {}, {
        staticParam: 'staticValue',
        webspace: 'webspace-attribute-value',
        pageTemplate: 'template-attribute-value',
    });
});

test('Should open FormOverlay with correct props when List fires the item-add callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
                addOverlayTitle: 'app.add_overlay_title',
                overlaySize: 'large',
            },
        },
    }: any);

    render(<FormOverlayList route={route} router={router} />);

    act(() => {
        getLatestListProps().onItemAdd();
    });

    const overlayProps = getLatestOverlayProps();

    expect(overlayProps).toEqual(expect.objectContaining({
        confirmText: 'sulu_admin.save',
        formStore: getLatestFormStore(),
        open: true,
        size: 'large',
        title: 'app.add_overlay_title',
    }));
});

test('Should open FormOverlay with correct props when List fires the item-click callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
                editOverlayTitle: 'app.edit_overlay_title',
            },
        },
    }: any);

    render(<FormOverlayList route={route} router={router} />);

    act(() => {
        getLatestListProps().onItemClick('item-id');
    });

    const overlayProps = getLatestOverlayProps();

    expect(overlayProps).toEqual(expect.objectContaining({
        confirmText: 'sulu_admin.save',
        formStore: getLatestFormStore(),
        open: true,
        size: 'small',
        title: 'app.edit_overlay_title',
    }));
});

test('Should destroy ResourceFormStore without reloading List when FormOverlay is closed', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    render(<FormOverlayList route={route} router={router} />);

    act(() => {
        getLatestListProps().onItemAdd();
    });

    const formStore = getLatestFormStore();
    const listReload = getLatestListReload();

    act(() => {
        getLatestOverlayProps().onClose();
    });

    expect(formStore.destroy).toBeCalled();
    expect(listReload).not.toBeCalled();
});

test('Should destroy ResourceFormStore and reload List view when FormOverlay is confirmed', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    render(<FormOverlayList route={route} router={router} />);

    act(() => {
        getLatestListProps().onItemAdd();
    });

    const formStore = getLatestFormStore();
    const listReload = getLatestListReload();

    act(() => {
        getLatestOverlayProps().onConfirm();
    });

    expect(formStore.destroy).toBeCalled();
    expect(listReload).toBeCalled();
});

test('Should destroy ResourceFormStore when component is unmounted', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    const {unmount} = render(<FormOverlayList route={route} router={router} />);

    act(() => {
        getLatestListProps().onItemAdd();
    });

    const formStore = getLatestFormStore();

    unmount();
    expect(formStore.destroy).toBeCalled();
});
