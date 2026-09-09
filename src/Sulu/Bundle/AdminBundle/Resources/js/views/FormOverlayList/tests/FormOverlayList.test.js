// @flow
import mockReact from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import FormOverlayList from '../FormOverlayList';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import Router from '../../../services/Router';
import formToolbarActionRegistry from '../../Form/registries/formToolbarActionRegistry';

const React = mockReact;

const mockListReload = jest.fn();

// the mock exposes the list callbacks as buttons, so a test can reach them by clicking
jest.mock('../../List', () => class ListMock extends mockReact.Component<*> {
    reload = mockListReload;

    handleItemAdd = () => this.props.onItemAdd && this.props.onItemAdd();

    handleItemClick = () => this.props.onItemClick && this.props.onItemClick('item-id');

    render() {
        return (
            <div data-testid="list">
                list view mock
                <button onClick={this.handleItemAdd} type="button">add item</button>
                <button onClick={this.handleItemClick} type="button">click item</button>
            </div>
        );
    }
});

jest.mock('../../../containers/Form/Form', () => class FormMock extends mockReact.Component<*> {
    // the overlay submits through this ref, the real Form turns that into an onSubmit call
    submit = jest.fn((options) => this.props.onSubmit(options));

    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../services/initializer', () => ({
    initializedTranslationsLocale: true,
}));

jest.mock('debounce', () => jest.fn((callback) => callback));

beforeEach(() => {
    mockListReload.mockClear();

    // The overlay toolbar renders the real Toolbar, whose Items component observes its size.
    window.ResizeObserver = jest.fn(function() {
        this.observe = jest.fn();
        this.disconnect = jest.fn();
    });
});

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
            // the overlay disables its confirm button for a pristine form
            dirty: true,
            id: resourceStore.id,
            metadataOptions,
            // Read by the toolbar action provider as parentResourceStore.
            resourceStore,
        };
    }
));

function createRouter(options: Object) {
    return ({route: {options}}: any);
}

// the form store the component created, so a test can assert on its destroy without reaching inside
function lastFormStore() {
    const results = (ResourceFormStore: any).mock.results;

    return results[results.length - 1].value;
}

// the overlay header close icon, told apart from the snackbar close icon that shares its label
function overlayCloseButton() {
    const header: any = document.querySelector('.header');

    return within(header).getByRole('button', {name: 'su-times'});
}

function createToolbarActionClass() {
    return jest.fn(function() {
        this.destroy = jest.fn();
        this.getNode = jest.fn(() => null);
        this.getToolbarItemConfig = jest.fn(() => ({label: 'Save', onClick: jest.fn(), type: 'button'}));
    });
}

test('View should render with closed overlay', () => {
    render(<FormOverlayList route={({}: any)} router={createRouter({})} />);

    expect(document.body).toMatchSnapshot();
});

test('View should render with opened overlay', async() => {
    const user = userEvent.setup();
    const router: Router = createRouter({
        addOverlayTitle: 'app.add_overlay_title',
        formKey: 'test-form-key',
    });

    render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add item'}));

    expect(document.body).toMatchSnapshot();
});

test('Should render the List view with its callbacks connected', () => {
    const router: Router = createRouter({
        adapters: ['table'],
        formKey: 'test-form-key',
        listKey: 'test-list-key',
        resourceKey: 'test-resource-key',
    });

    render(<FormOverlayList route={({}: any)} router={router} />);

    expect(screen.getByTestId('list')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'add item'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'click item'})).toBeInTheDocument();
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-add callback', async() => {
    const user = userEvent.setup();
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

    render(<FormOverlayList resourceStore={testResourceStore} route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add item'}));

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
    render(
        <FormOverlayList
            locale={locale}
            resourceStore={testResourceStore}
            route={({}: any)}
            router={router}
        />
    );
    await user.click(screen.getByRole('button', {name: 'click item'}));

    expect(ResourceStore).toHaveBeenCalledWith('test-resource-key', 'item-id', {}, {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    });
});

test('Should construct ResourceFormStore with correct metadataOptions on item-add callback', async() => {
    const user = userEvent.setup();
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
                metadataRequestParameters: {'test-parameter': 'test-value'},
                routerAttributesToFormMetadata: {'0': 'webspace', 'template': 'formTemplate'},
            },
        },
    }: any);

    render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add item'}));

    expect(ResourceFormStore).toHaveBeenCalledWith(expect.anything(), 'test-form-key', {}, {
        'test-parameter': 'test-value',
        webspace: 'webspace-attribute-value',
        formTemplate: 'template-attribute-value',
    });
});

test('Should open the overlay with the add title when List fires the item-add callback', async() => {
    const user = userEvent.setup();
    const router: Router = createRouter({
        addOverlayTitle: 'app.add_overlay_title',
        formKey: 'test-form-key',
        overlaySize: 'large',
    });

    render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add item'}));

    expect(screen.getByRole('heading', {name: 'app.add_overlay_title'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeInTheDocument();
    expect(document.querySelector('.overlay')).toHaveClass('large');
});

test('Should open the overlay with the edit title when List fires the item-click callback', async() => {
    const user = userEvent.setup();
    const router: Router = createRouter({
        editOverlayTitle: 'app.edit_overlay_title',
        formKey: 'test-form-key',
    });

    render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'click item'}));

    expect(screen.getByRole('heading', {name: 'app.edit_overlay_title'})).toBeInTheDocument();
    expect(document.querySelector('.overlay')).toHaveClass('small');
});

test('Should destroy ResourceFormStore without reloading List when FormOverlay is closed', async() => {
    const user = userEvent.setup();
    const router: Router = createRouter({formKey: 'test-form-key'});

    render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add item'}));

    const formStore = lastFormStore();
    await user.click(overlayCloseButton());

    expect(formStore.destroy).toHaveBeenCalled();
    expect(mockListReload).not.toHaveBeenCalled();
});

test('Should destroy ResourceFormStore and reload List view when FormOverlay is confirmed', async() => {
    const user = userEvent.setup();
    const router: Router = createRouter({formKey: 'test-form-key'});

    render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add item'}));

    const formStore = lastFormStore();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.save'}));

    expect(formStore.destroy).toHaveBeenCalled();
    expect(mockListReload).toHaveBeenCalled();
});

test('Should destroy ResourceFormStore when component is unmounted', async() => {
    const user = userEvent.setup();
    const router: Router = createRouter({formKey: 'test-form-key'});

    const {unmount} = render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'add item'}));

    const formStore = lastFormStore();
    unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});

test('Should resolve overlayToolbarActions from the registry and render them in the overlay', async() => {
    const user = userEvent.setup();
    const SaveToolbarAction = createToolbarActionClass();
    formToolbarActionRegistry.clear();
    formToolbarActionRegistry.add('sulu_admin.save', (SaveToolbarAction: any));

    try {
        const router: Router = ({
            route: {
                name: 'test-route',
                options: {
                    formKey: 'test-form-key',
                    locales: ['en', 'de'],
                    overlayToolbarActions: [{options: {}, type: 'sulu_admin.save'}],
                    resourceKey: 'test-resource-key',
                },
            },
        }: any);

        render(<FormOverlayList route={({}: any)} router={router} />);
        await user.click(screen.getByRole('button', {name: 'click item'}));

        // the toolbar renders the action, and the footer confirm gives way to it
        expect(screen.getByRole('button', {name: 'Save'})).toBeInTheDocument();
        expect(screen.queryByRole('button', {name: 'sulu_admin.save'})).not.toBeInTheDocument();

        expect(SaveToolbarAction).toHaveBeenCalledTimes(1);

        const [formStore, form, passedRouter, locales, , parentResourceStore] = SaveToolbarAction.mock.calls[0];

        expect(formStore.id).toEqual(lastFormStore().id);
        expect(form).toBeDefined();
        expect(passedRouter).toBe(router);
        expect(locales).toEqual(['en', 'de']);
        expect(parentResourceStore).toBeDefined();
    } finally {
        formToolbarActionRegistry.clear();
    }
});

test('Should reload the list when an overlay with toolbar actions is closed', async() => {
    const user = userEvent.setup();
    formToolbarActionRegistry.clear();
    formToolbarActionRegistry.add('sulu_admin.save', (createToolbarActionClass(): any));

    try {
        const router: Router = ({
            route: {
                name: 'test-route',
                options: {
                    formKey: 'test-form-key',
                    overlayToolbarActions: [{options: {}, type: 'sulu_admin.save'}],
                    resourceKey: 'test-resource-key',
                },
            },
        }: any);

        render(<FormOverlayList route={({}: any)} router={router} />);
        await user.click(screen.getByRole('button', {name: 'click item'}));
        await user.click(overlayCloseButton());

        expect(mockListReload).toHaveBeenCalled();
    } finally {
        formToolbarActionRegistry.clear();
    }
});

test('Should not reload the list when an overlay without toolbar actions is closed', async() => {
    const user = userEvent.setup();
    const router: Router = createRouter({formKey: 'test-form-key', resourceKey: 'test-resource-key'});

    render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'click item'}));
    await user.click(overlayCloseButton());

    expect(mockListReload).not.toHaveBeenCalled();
});

test('Should render the footer confirm when no overlayToolbarActions are configured', async() => {
    const user = userEvent.setup();
    const router: Router = createRouter({formKey: 'test-form-key', resourceKey: 'test-resource-key'});

    render(<FormOverlayList route={({}: any)} router={router} />);
    await user.click(screen.getByRole('button', {name: 'click item'}));

    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeInTheDocument();
});

test('Should throw when sulu_admin.copy is configured as an overlay toolbar action', () => {
    const router: Router = createRouter({
        formKey: 'test-form-key',
        overlayToolbarActions: [{options: {}, type: 'sulu_admin.copy'}],
        resourceKey: 'test-resource-key',
    });

    expect(() => render(<FormOverlayList route={({}: any)} router={router} />)).toThrow(/sulu_admin.copy/);
});

test('Should throw when sulu_admin.delete is configured as an overlay toolbar action', () => {
    const router: Router = createRouter({
        formKey: 'test-form-key',
        overlayToolbarActions: [{options: {}, type: 'sulu_admin.delete'}],
        resourceKey: 'test-resource-key',
    });

    expect(() => render(<FormOverlayList route={({}: any)} router={router} />)).toThrow(/sulu_admin.delete/);
});
