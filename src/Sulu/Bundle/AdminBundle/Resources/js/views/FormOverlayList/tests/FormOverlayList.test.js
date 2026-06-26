// @flow
/* eslint-disable react/jsx-no-bind */
import mockReact from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FormOverlayList from '../FormOverlayList';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import Router, {Route} from '../../../services/Router';
import FormOverlay from '../../../containers/FormOverlay';
import {createTestRef, getMockProps} from '../../../utils/TestHelper';

const React = mockReact;
let mockListReload;

jest.mock('../../List', () => {
    const React = require('react');

    const ListMock = React.forwardRef((props, ref) => {
        React.useImperativeHandle(ref, () => ({
            reload: mockListReload,
        }));

        return (
            <div
                data-has-item-add={props.onItemAdd ? 'true' : 'false'}
                data-has-item-click={props.onItemClick ? 'true' : 'false'}
                data-has-locale={props.locale ? 'true' : 'false'}
                data-testid="list-view"
            >
                list view mock
                {props.onItemAdd &&
                    <button onClick={props.onItemAdd} type="button">add-item</button>
                }
                {props.onItemClick &&
                    <button onClick={() => props.onItemClick('item-id')} type="button">click-item</button>
                }
            </div>
        );
    });

    ListMock.displayName = 'ListMock';
    (ListMock: any).getDerivedRouteAttributes = jest.fn();

    return ListMock;
});

jest.mock('../../../containers/Form/Form', () => class FormMock extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('../../../containers/FormOverlay', () => {
    const React = require('react');
    const {createComponentMock} = require('../../../utils/TestHelper/componentMocks');

    return createComponentMock((props) => (
        <div>
            <button onClick={props.onClose} type="button">close-overlay</button>
            <button onClick={props.onConfirm} type="button">confirm-overlay</button>
        </div>
    ));
});

jest.mock('../../../utils/Translator');

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
            dirty: true,
            id: resourceStore.id,
            metadataOptions,
        };
    }
));

beforeEach(() => {
    mockListReload = jest.fn();
    (ResourceStore: any).mockClear();
    (ResourceFormStore: any).mockClear();
    (FormOverlay: any).mockClear();
});

test('View should render with closed overlay', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {},
        },
    }: any);

    render(<FormOverlayList route={route} router={router} />);

    expect(screen.getByTestId('list-view')).toBeInTheDocument();
    expect(FormOverlay).not.toHaveBeenCalled();
});

test('View should render with opened overlay', async() => {
    const user = userEvent.setup();
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                addOverlayTitle: 'app.add_overlay_title',
                formKey: 'test-form-key',
            },
        },
    }: any);

    render(<FormOverlayList route={route} router={router} />);

    await user.click(screen.getByRole('button', {name: 'add-item'}));

    expect(getMockProps(FormOverlay).open).toEqual(true);
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

    expect(screen.getByTestId('list-view')).toHaveAttribute('data-has-locale', 'true');
    expect(screen.getByTestId('list-view')).toHaveAttribute('data-has-item-add', 'true');
    expect(screen.getByTestId('list-view')).toHaveAttribute('data-has-item-click', 'true');
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-add callback', async() => {
    const user = userEvent.setup();
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
    await user.click(screen.getByRole('button', {name: 'add-item'}));

    expect(ResourceStore).toHaveBeenCalledWith('test-resource-key', undefined, {}, {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    });
    expect(ResourceFormStore).toHaveBeenCalledWith(expect.anything(), 'test-form-key', {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    }, {});
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-click callback', async() => {
    const user = userEvent.setup();
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
    const locale = observable.box('en');
    const ref = createTestRef();

    render(<FormOverlayList ref={ref} resourceStore={testResourceStore} route={route} router={router} />);
    ref.current.locale = locale;

    await user.click(screen.getByRole('button', {name: 'click-item'}));

    expect(ResourceStore).toHaveBeenCalledWith('test-resource-key', 'item-id', {locale}, {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    });
    expect(ResourceFormStore).toHaveBeenCalledWith(expect.anything(), 'test-form-key', {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    }, {});
});

test('Should construct ResourceFormStore with correct metadataOptions on item-add callback', async() => {
    const user = userEvent.setup();
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
    const ref = createTestRef();

    render(<FormOverlayList ref={ref} route={route} router={router} />);
    ref.current.locale = observable.box('en');
    await user.click(screen.getByRole('button', {name: 'add-item'}));

    expect(ResourceFormStore).toHaveBeenCalledWith(expect.anything(), 'test-form-key', {}, {
        staticParam: 'staticValue',
        webspace: 'webspace-attribute-value',
        pageTemplate: 'template-attribute-value',
    });
});

test('Should open FormOverlay with correct props when List fires the item-add callback', async() => {
    const user = userEvent.setup();
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
    await user.click(screen.getByRole('button', {name: 'add-item'}));

    expect(getMockProps(FormOverlay)).toEqual(expect.objectContaining({
        confirmText: 'sulu_admin.save',
        open: true,
        size: 'large',
        title: 'app.add_overlay_title',
    }));
});

test('Should open FormOverlay with correct props when List fires the item-click callback', async() => {
    const user = userEvent.setup();
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
    await user.click(screen.getByRole('button', {name: 'click-item'}));

    expect(getMockProps(FormOverlay)).toEqual(expect.objectContaining({
        confirmText: 'sulu_admin.save',
        formStore: expect.objectContaining({id: 'item-id'}),
        open: true,
        size: 'small',
        title: 'app.edit_overlay_title',
    }));
});

test('Should destroy ResourceFormStore without reloading List when FormOverlay is closed', async() => {
    const user = userEvent.setup();
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);
    const ref = createTestRef();

    render(<FormOverlayList ref={ref} route={route} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add-item'}));

    const destroySpy = jest.fn();
    ref.current.formStore.destroy = destroySpy;

    await user.click(screen.getByRole('button', {name: 'close-overlay'}));
    expect(destroySpy).toHaveBeenCalled();
    expect(mockListReload).not.toHaveBeenCalled();
});

test('Should destroy ResourceFormStore and reload List view when FormOverlay is confirmed', async() => {
    const user = userEvent.setup();
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);
    const ref = createTestRef();

    render(<FormOverlayList ref={ref} route={route} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add-item'}));

    const destroySpy = jest.fn();
    ref.current.formStore.destroy = destroySpy;

    await user.click(screen.getByRole('button', {name: 'confirm-overlay'}));
    expect(destroySpy).toHaveBeenCalled();
    expect(mockListReload).toHaveBeenCalled();
});

test('Should destroy ResourceFormStore when component is unmounted', async() => {
    const user = userEvent.setup();
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);
    const ref = createTestRef();

    const {unmount} = render(<FormOverlayList ref={ref} route={route} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add-item'}));

    const destroySpy = jest.fn();
    ref.current.formStore.destroy = destroySpy;

    unmount();
    expect(destroySpy).toHaveBeenCalled();
});
