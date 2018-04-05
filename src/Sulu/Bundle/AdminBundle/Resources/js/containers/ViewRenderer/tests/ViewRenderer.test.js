/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import ViewRenderer from '../ViewRenderer';
import viewRegistry from '../registries/ViewRegistry';
import sidebarStore from '../../Sidebar/stores/SidebarStore';

jest.mock('../registries/ViewRegistry', () => ({
    get: jest.fn(),
}));

jest.mock('../../Sidebar/stores/SidebarStore', () => ({
    clearConfig: jest.fn(),
}));

test('Render view returned from ViewRegistry', () => {
    viewRegistry.get.mockReturnValue(() => (<h1>Test</h1>));
    const view = render(<ViewRenderer router={{route: {view: 'test'}}} />);
    expect(view).toMatchSnapshot();
    expect(viewRegistry.get).toBeCalledWith('test');
    expect(sidebarStore.clearConfig).toBeCalled();
});

test('Render view returned from ViewRegistry with passed router', () => {
    const router = {
        route: {
            view: 'test',
        },
        attributes: {
            value: 'Test attribute',
        },
    };

    viewRegistry.get.mockReturnValue((props) => (<h1>{props.router.attributes.value}</h1>));
    const view = render(<ViewRenderer router={router} />);
    expect(view).toMatchSnapshot();
    expect(viewRegistry.get).toBeCalledWith('test');
});

test('Render view should throw if view does not exist', () => {
    viewRegistry.get.mockReturnValue(undefined);
    expect(() => render(<ViewRenderer router={{route: {view: 'not_existing'}}} />)).toThrow(/not_existing/);
});

test('Render view with parents should nest rendered views', () => {
    const router = {
        route: {
            name: 'sulu_admin.form_tab',
            view: 'form_tab',
            parent: {
                name: 'sulu_admin.form',
                view: 'form',
                parent: {
                    name: 'sulu_admin.app',
                    view: 'app',
                },
            },
        },
    };

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

    expect(render(<ViewRenderer router={router} />)).toMatchSnapshot();
});

test('Render view with parents should nest rendered views and correctly pass children arguments', () => {
    const router = {
        route: {
            name: 'sulu_admin.form_tab',
            view: 'form_tab',
            parent: {
                name: 'sulu_admin.form',
                view: 'form',
                parent: {
                    name: 'sulu_admin.app',
                    view: 'app',
                },
            },
        },
    };

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

    expect(render(<ViewRenderer router={router} />)).toMatchSnapshot();
});

test('Render view with not existing parent should throw', () => {
    const router = {
        route: {
            view: 'form_tab',
            parent: {
                view: 'form',
                parent: {
                    view: 'app',
                },
            },
        },
    };

    viewRegistry.get.mockImplementation((view) => {
        switch (view) {
            case 'form_tab':
                return function FormTab() {
                    return (
                        <div>
                            <h3>Form Tab</h3>
                        </div>
                    );
                };
            case 'form':
                return function Form(props) {
                    return (
                        <div>
                            <h2>Form</h2>
                            {props.children}
                        </div>
                    );
                };
        }
    });

    expect(() => render(<ViewRenderer router={router} />)).toThrow(/app/);
});

test('Render view with route that has no rerenderAttributes', () => {
    const router = {
        route: {
            view: 'webspaceOverview',
        },
        attributes: {
            webspace: 'test',
        },
    };

    viewRegistry.get.mockImplementation((view) => {
        switch (view) {
            case 'webspaceOverview':
                return function WebspaceOverview() {
                    return (
                        <div>
                            <h3>Webspace</h3>
                        </div>
                    );
                };
        }
    });

    const viewRenderer = shallow(<ViewRenderer router={router} />);
    expect(viewRenderer.key()).toBe(undefined);
});

test('Render view with route that has rerenderAttributes', () => {
    const router = {
        route: {
            view: 'webspaceOverview',
            rerenderAttributes: [
                'webspace',
            ],
        },
        attributes: {
            webspace: 'test',
        },
    };

    viewRegistry.get.mockImplementation((view) => {
        switch (view) {
            case 'webspaceOverview':
                return function WebspaceOverview() {
                    return (
                        <div>
                            <h3>Webspace</h3>
                        </div>
                    );
                };
        }
    });

    const viewRenderer = shallow(<ViewRenderer router={router} />);
    expect(viewRenderer.key()).toBe('test');
});

test('Render view with route that has more than one rerenderAttributes', () => {
    const router = {
        route: {
            view: 'webspaceOverview',
            rerenderAttributes: [
                'webspace',
                'locale',
            ],
        },
        attributes: {
            webspace: 'test',
            locale: 'de',
        },
    };

    viewRegistry.get.mockImplementation((view) => {
        switch (view) {
            case 'webspaceOverview':
                return function WebspaceOverview() {
                    return (
                        <div>
                            <h3>Webspace</h3>
                        </div>
                    );
                };
        }
    });

    const viewRenderer = shallow(<ViewRenderer router={router} />);
    expect(viewRenderer.key()).toBe('test__de');
});

test('Render view and not clear the sidebarstore when component has sidebar', () => {
    const Component = class Component extends React.Component {
        static hasSidebar = true;

        render() {
            return <h1>{this.props.title}</h1>;
        }
    };

    viewRegistry.get.mockReturnValue(Component);
    const view = render(<ViewRenderer router={{route: {view: 'test'}}} />);
    expect(view).toMatchSnapshot();
    expect(viewRegistry.get).toBeCalledWith('test');
    expect(sidebarStore.clearConfig).not.toBeCalled();
});
