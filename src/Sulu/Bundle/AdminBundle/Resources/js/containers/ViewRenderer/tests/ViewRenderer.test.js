/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, screen} from '@testing-library/react';
import ViewRenderer from '../ViewRenderer';
import viewRegistry from '../registries/viewRegistry';

jest.mock('../registries/viewRegistry', () => ({
    get: jest.fn(),
    getConfig: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function createRouter(route, attributes = {}) {
    return {
        addUpdateRouteHook: jest.fn(),
        attributes,
        clearBindings: jest.fn(),
        route,
    };
}

function RemountProbeView(props) {
    const [initialAttributes] = React.useState(props.router.attributes);

    return (
        <div data-testid="remount-probe">
            {initialAttributes.webspace}
            {initialAttributes.locale ? '__' + initialAttributes.locale : ''}
        </div>
    );
}

test('Render view returned from ViewRegistry', () => {
    const router = createRouter({type: 'test'});
    viewRegistry.get.mockReturnValue(() => (<h1>Test</h1>));
    viewRegistry.getConfig.mockReturnValue({});

    const {container} = render(<ViewRenderer router={router} />);

    expect(screen.getByRole('heading', {name: 'Test'})).toBeInTheDocument();
    expect(container.firstChild).toHaveClass('view');
    expect(viewRegistry.get).toHaveBeenCalledWith('test');
});

test('Render view returned from ViewRegistry with disableDefaultSpacing true', () => {
    const router = createRouter({type: 'test'});
    viewRegistry.get.mockReturnValue(() => (<h1>Test</h1>));
    viewRegistry.getConfig.mockReturnValue({disableDefaultSpacing: true});

    const {container} = render(<ViewRenderer router={router} />);

    expect(screen.getByRole('heading', {name: 'Test'})).toBeInTheDocument();
    expect(container.firstChild.tagName).toEqual('H1');
    expect(viewRegistry.get).toHaveBeenCalledWith('test');
});

test('Render view returned from ViewRegistry with passed router', () => {
    const router = createRouter(
        {
            type: 'test',
        },
        {
            value: 'Test attribute',
        }
    );

    viewRegistry.get.mockReturnValue((props) => (<h1>{props.router.attributes.value}</h1>));
    viewRegistry.getConfig.mockReturnValue({});

    render(<ViewRenderer router={router} />);

    expect(screen.getByRole('heading', {name: 'Test attribute'})).toBeInTheDocument();
    expect(viewRegistry.get).toHaveBeenCalledWith('test');
});

test('Render view with parents should nest rendered views', () => {
    const router = createRouter({
        name: 'sulu_admin.form_tab',
        type: 'form_tab',
        parent: {
            name: 'sulu_admin.form',
            type: 'form',
            parent: {
                name: 'sulu_admin.app',
                type: 'app',
            },
        },
    });

    viewRegistry.get.mockImplementation((view) => {
        switch (view) {
            case 'form_tab':
                return function FormTab(props) {
                    return (
                        <div>
                            <h3>Form Tab</h3>
                            {props.route.name}
                        </div>
                    );
                };
            case 'form':
                return function Form(props) {
                    return (
                        <div>
                            <h2>Form</h2>
                            {props.route.name}
                            {props.children()}
                        </div>
                    );
                };
            case 'app':
                return function App(props) {
                    return (
                        <div>
                            <h1>App</h1>
                            {props.route.name}
                            {props.children()}
                        </div>
                    );
                };
        }
    });
    viewRegistry.getConfig.mockReturnValue({});

    render(<ViewRenderer router={router} />);

    expect(screen.getByRole('heading', {name: 'App'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'Form'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'Form Tab'})).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.app')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.form')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.form_tab')).toBeInTheDocument();
});

test('Render view with parents should nest rendered views and correctly pass children arguments', () => {
    const router = createRouter({
        name: 'sulu_admin.form_tab',
        type: 'form_tab',
        parent: {
            name: 'sulu_admin.form',
            type: 'form',
            parent: {
                name: 'sulu_admin.app',
                type: 'app',
            },
        },
    });

    viewRegistry.get.mockImplementation((view) => {
        switch (view) {
            case 'form_tab':
                return function FormTab(props) {
                    return (
                        <div>
                            <h3>Form Tab</h3>
                            <p>{props.route.name}</p>
                            <p>{props.form}</p>
                        </div>
                    );
                };
            case 'form':
                return function Form(props) {
                    return (
                        <div>
                            <h2>Form</h2>
                            <p>{props.route.name}</p>
                            <p>{props.app}</p>
                            {props.children({form: 'Form'})}
                        </div>
                    );
                };
            case 'app':
                return function App(props) {
                    return (
                        <div>
                            <h1>App</h1>
                            <p>{props.route.name}</p>
                            {props.children({app: 'App'})}
                        </div>
                    );
                };
        }
    });
    viewRegistry.getConfig.mockReturnValue({disableDefaultSpacing: true});

    render(<ViewRenderer router={router} />);

    expect(screen.getByRole('heading', {name: 'App'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'Form'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'Form Tab'})).toBeInTheDocument();
    expect(screen.getAllByText('App')).toHaveLength(2);
    expect(screen.getAllByText('Form')).toHaveLength(2);
});

test('Render view with route that has no rerenderAttributes', () => {
    const route = {
        name: 'route',
        type: 'webspaceOverview',
    };
    const router = createRouter(route, {webspace: 'test'});
    viewRegistry.get.mockReturnValue(RemountProbeView);
    viewRegistry.getConfig.mockReturnValue({disableDefaultSpacing: true});

    const {rerender} = render(<ViewRenderer router={router} />);

    expect(screen.getByTestId('remount-probe')).toHaveTextContent('test');

    rerender(<ViewRenderer router={createRouter(route, {webspace: 'example'})} />);

    expect(screen.getByTestId('remount-probe')).toHaveTextContent('test');
});

test('Render view with route that has rerenderAttributes', () => {
    const route = {
        name: 'route',
        type: 'webspaceOverview',
        rerenderAttributes: [
            'webspace',
        ],
    };
    const router = createRouter(route, {webspace: 'test'});
    viewRegistry.get.mockReturnValue(RemountProbeView);
    viewRegistry.getConfig.mockReturnValue({disableDefaultSpacing: true});

    const {rerender} = render(<ViewRenderer router={router} />);

    expect(screen.getByTestId('remount-probe')).toHaveTextContent('test');

    rerender(<ViewRenderer router={createRouter(route, {webspace: 'example'})} />);

    expect(screen.getByTestId('remount-probe')).toHaveTextContent('example');
});

test('Render view with route that has more than one rerenderAttributes', () => {
    const route = {
        name: 'route',
        type: 'webspaceOverview',
        rerenderAttributes: [
            'webspace',
            'locale',
        ],
    };
    const router = createRouter(route, {locale: 'de', webspace: 'test'});
    viewRegistry.get.mockReturnValue(RemountProbeView);
    viewRegistry.getConfig.mockReturnValue({disableDefaultSpacing: true});

    const {rerender} = render(<ViewRenderer router={router} />);

    expect(screen.getByTestId('remount-probe')).toHaveTextContent('test__de');

    rerender(<ViewRenderer router={createRouter(route, {locale: 'fr', webspace: 'test'})} />);

    expect(screen.getByTestId('remount-probe')).toHaveTextContent('test__fr');
});

test('Clear bindings of router everytime a new view is rendered', () => {
    viewRegistry.get.mockReturnValue(() => (<h1>Test</h1>));
    viewRegistry.getConfig.mockReturnValue({});

    const route1 = {
        name: 'test1',
        type: 'test',
    };

    const route2 = {
        name: 'test2',
        type: 'test',
    };

    const router = createRouter(route1);

    render(<ViewRenderer router={router} />);
    expect(router.addUpdateRouteHook).toHaveBeenCalledWith(expect.anything(), 1024);

    const updateRouteHook = router.addUpdateRouteHook.mock.calls[0][0];

    updateRouteHook(route1, {});
    expect(router.clearBindings).not.toHaveBeenCalled();

    updateRouteHook(route2, {});
    expect(router.clearBindings).toHaveBeenCalledWith();
});

test('Clear bindings of router when same view with a different rerender attribute is rendered', () => {
    viewRegistry.get.mockReturnValue(() => (<h1>Test</h1>));
    viewRegistry.getConfig.mockReturnValue({});

    const route = {
        name: 'test1',
        type: 'test',
        rerenderAttributes: ['webspace'],
    };

    const router = createRouter(route, {
        locale: 'de',
        webspace: 'sulu',
    });

    render(<ViewRenderer router={router} />);
    expect(router.addUpdateRouteHook).toHaveBeenCalledWith(expect.anything(), 1024);

    const updateRouteHook = router.addUpdateRouteHook.mock.calls[0][0];

    updateRouteHook(route, {locale: 'en', webspace: 'sulu'});
    expect(router.clearBindings).not.toHaveBeenCalled();

    updateRouteHook(route, {locale: 'de', webspace: 'example'});
    expect(router.clearBindings).toHaveBeenCalledWith();
});
