/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Form from '../Form';
import formToolbarActionRegistry from '../registries/formToolbarActionRegistry';
import ResourceStore from '../../../stores/ResourceStore';
import CollaborationStore from '../../../stores/CollaborationStore';
import {resourceFormStoreFactory} from '../../../containers/Form';

jest.mock('../../../services/initializer', () => jest.fn());
jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));
jest.mock('../../../containers/Form', () => {
    const React = require('react');

    const formSubmitMock = jest.fn();
    const formContainerRenderMock = jest.fn(function FormContainer(props, ref) {
        React.useImperativeHandle(ref, () => ({
            submit: formSubmitMock,
        }));

        return <div data-testid="form-container" />;
    });
    const FormContainer = React.forwardRef(formContainerRenderMock);
    FormContainer.displayName = 'FormContainer';

    return {
        __esModule: true,
        default: FormContainer,
        ResourceFormStore: jest.fn(),
        resourceFormStoreFactory: {
            createFromResourceStore: jest.fn(),
        },
        __mocks: {
            formContainerRenderMock,
            formSubmitMock,
        },
    };
});

jest.mock('../../../stores/ResourceStore', () => {
    const {observable} = require('mobx');

    return jest.fn(function(resourceKey, id, options = {}, requestOptions = {}, idQueryParameter) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.locale = options.locale || observable.box('en');
        this.destroy = jest.fn();
        this.requestOptions = requestOptions;
        this.idQueryParameter = idQueryParameter;
    });
});

jest.mock('../../../stores/CollaborationStore', () => jest.fn(function(resourceKey, id) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.collaborations = [];
    this.destroy = jest.fn();
}));

jest.mock('../registries/formToolbarActionRegistry', () => ({
    __esModule: true,
    default: {
        get: jest.fn(),
    },
}));

const containersFormMock = jest.requireMock('../../../containers/Form');
const formContainerRenderMock = containersFormMock.__mocks.formContainerRenderMock;
const formSubmitMock = containersFormMock.__mocks.formSubmitMock;

const getFormContainerProps = () => {
    const calls = formContainerRenderMock.mock.calls;
    if (calls.length === 0) {
        throw new Error('Expected FormContainer to be rendered');
    }

    return calls[calls.length - 1][0];
};

const createResourceFormStore = (overrides = {}) => ({
    data: {},
    dirty: false,
    destroy: jest.fn(),
    save: jest.fn(() => Promise.resolve({id: 1})),
    ...overrides,
});

const createRoute = (options = {}) => ({
    options: {
        formKey: 'snippets',
        resourceKey: 'snippets',
        toolbarActions: [],
        ...options,
    },
});

const createRouter = (route, attributes = {}) => ({
    addUpdateRouteHook: jest.fn(() => jest.fn()),
    attributes,
    bind: jest.fn(),
    navigate: jest.fn(),
    restore: jest.fn(),
    route,
});

beforeEach(() => {
    jest.clearAllMocks();
    resourceFormStoreFactory.createFromResourceStore.mockReturnValue(createResourceFormStore());
});

test('renders form with title when titleVisible is enabled', () => {
    const route = createRoute({titleVisible: true});
    const router = createRouter(route, {id: 10});
    const resourceStore = new ResourceStore('snippets', 10, {locale: observable.box('en')});

    const {asFragment} = render(
        <Form
            resourceStore={resourceStore}
            route={route}
            router={router}
            title="Test title"
        />
    );

    expect(screen.getByText('Test title')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('reuses passed resourceStore when resourceKey matches', () => {
    const route = createRoute({resourceKey: 'snippets'});
    const router = createRouter(route, {id: 10});
    const resourceStore = new ResourceStore('snippets', 10);

    render(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(resourceFormStoreFactory.createFromResourceStore.mock.calls[0][0]).toBe(resourceStore);
});

test('creates a new resourceStore when resourceKey differs', () => {
    const route = createRoute({resourceKey: 'pages'});
    const router = createRouter(route, {id: 10});
    const resourceStore = new ResourceStore('snippets', 10);

    render(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(resourceFormStoreFactory.createFromResourceStore.mock.calls[0][0]).not.toBe(resourceStore);
    expect(ResourceStore).toHaveBeenLastCalledWith('pages', 10, {locale: resourceStore.locale}, {});
});

test('delegates submit to form container ref', async() => {
    const user = userEvent.setup();
    const route = createRoute({toolbarActions: [{type: 'mock', options: {}}]});
    const router = createRouter(route, {id: 10});
    const resourceStore = new ResourceStore('snippets', 10);
    const ToolbarAction = jest.fn(function(resourceFormStore, form) {
        this.handleSubmitClick = jest.fn(function() {
            form.submit({action: 'save'});
        });
        this.getNode = jest.fn((index) => (
            <button key={index} onClick={this.handleSubmitClick} type="button">
                Submit
            </button>
        ));
        this.getToolbarItemConfig = jest.fn(() => ({}));
        this.setLocales = jest.fn();
        this.destroy = jest.fn();
    });
    formToolbarActionRegistry.get.mockReturnValue(ToolbarAction);

    render(
        <Form
            resourceStore={resourceStore}
            route={route}
            router={router}
        />
    );
    await user.click(screen.getByRole('button', {name: 'Submit'}));

    expect(formSubmitMock).toHaveBeenCalledWith({action: 'save'});
});

test('initializes and destroys toolbar actions', () => {
    const toolbarActionDestroy = jest.fn();
    const ToolbarAction = jest.fn(function() {
        this.getNode = jest.fn(() => <div key="toolbar-action">Toolbar Action</div>);
        this.getToolbarItemConfig = jest.fn(() => ({}));
        this.setLocales = jest.fn();
        this.destroy = toolbarActionDestroy;
    });
    formToolbarActionRegistry.get.mockReturnValue(ToolbarAction);

    const route = createRoute({
        toolbarActions: [
            {
                type: 'mock',
                options: {},
            },
        ],
    });
    const router = createRouter(route, {id: 10});
    const resourceStore = new ResourceStore('snippets', 10);

    const {unmount} = render(<Form resourceStore={resourceStore} route={route} router={router} />);
    unmount();

    expect(ToolbarAction).toHaveBeenCalled();
    expect(toolbarActionDestroy).toHaveBeenCalled();
});

test('creates collaboration store when resource key and id are present', () => {
    const route = createRoute({resourceKey: 'snippets'});
    const router = createRouter(route, {id: 55});
    const resourceStore = new ResourceStore('snippets', 55);

    render(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(CollaborationStore).toHaveBeenCalledWith('snippets', 55);
});

test('navigates to edit view after successful save', async() => {
    const formStore = createResourceFormStore({save: jest.fn(() => Promise.resolve({}))});
    resourceFormStoreFactory.createFromResourceStore.mockReturnValue(formStore);

    const route = createRoute({editView: 'form_edit'});
    const router = createRouter(route, {id: 11});
    const resourceStore = new ResourceStore('snippets', 11);

    render(<Form resourceStore={resourceStore} route={route} router={router} />);

    await getFormContainerProps().onSubmit({action: 'save'});

    await waitFor(() => expect(router.navigate).toHaveBeenCalledWith('form_edit', expect.objectContaining({
        id: resourceStore.id,
        locale: resourceStore.locale,
    })));
});

test('throws when formKey is missing', () => {
    const route = createRoute({formKey: undefined});
    const router = createRouter(route, {id: 10});
    const resourceStore = new ResourceStore('snippets', 10);

    expect(() => render(<Form resourceStore={resourceStore} route={route} router={router} />))
        .toThrow('mandatory "formKey" option');
});
