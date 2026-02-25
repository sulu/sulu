// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Router, Route} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import WebspaceTabs from '../WebspaceTabs';
import webspaceStore from '../../../stores/webspaceStore';

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.addUpdateRouteHook = jest.fn(() => jest.fn());
    this.bind = jest.fn();
}));

jest.mock('../../../stores/webspaceStore', () => ({
    grantedWebspaces: [],
    getWebspace: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/views', () => ({
    Tabs: jest.fn(({children, childrenProps, header}) => (
        <div>
            {header}
            {typeof children === 'function' ? children(childrenProps) : children}
        </div>
    )),
}));

const tabsMock = ((jest.requireMock('sulu-admin-bundle/views'): any).Tabs: {
    mock: {calls: Array<[Object]>},
    ...
});
const mockedWebspaceStore: any = webspaceStore;

const createRoute = () => new Route({
    name: 'webspace_tabs',
    path: '/webspace_tabs',
    type: 'webspace_tabs',
});

beforeEach(() => {
    jest.clearAllMocks();
    mockedWebspaceStore.grantedWebspaces = [];
});

test('Render webspace select with children when webspaces are not loaded yet', () => {
    const router = new Router({});
    const route = createRoute();

    const webspace = {key: 'sulu_blog', localizations: [{locale: 'en', default: false}, {locale: 'de', default: true}]};

    webspaceStore.getWebspace.mockImplementation((key) => {
        if (key === 'sulu_blog') {
            return webspace;
        }
    });

    router.bind.mockImplementation((key, webspaceKey) => {
        if (key === 'webspace') {
            webspaceKey.set('sulu_blog');
        }
    });

    const {asFragment} = render(
        <WebspaceTabs isRootView={true} route={route} router={router}>
            {(props) => <h1>{props && props.webspace && props.webspace.key}</h1>}
        </WebspaceTabs>
    );

    expect(screen.getByRole('button')).toBeInTheDocument();
    expect(tabsMock).toHaveBeenCalled();
    expect(asFragment()).toMatchSnapshot();
});

test('Load webspace userStore if no route attribute is given', () => {
    userStore.getPersistentSetting.mockImplementation((key) => {
        if (key === 'sulu_page.webspace_tabs.webspace') {
            return 'sulu';
        }
    });

    // $FlowFixMe
    expect(WebspaceTabs.getDerivedRouteAttributes(undefined, {})).toEqual({webspace: 'sulu'});
});

test('Load webspace from route attributes', () => {
    userStore.getPersistentSetting.mockImplementation((key) => {
        if (key === 'sulu_page.webspace_overview.webspace') {
            return 'sulu';
        }
    });

    // $FlowFixMe
    expect(WebspaceTabs.getDerivedRouteAttributes(undefined, {webspace: 'abc'})).toEqual({webspace: 'abc'});
});

test('Should bind and unbind router attributes and updateRouteHook', () => {
    const router = new Router({});
    const route = createRoute();

    const bindWebspaceToRouterDisposerSpy = jest.fn();
    router.addUpdateRouteHook.mockImplementationOnce(() => bindWebspaceToRouterDisposerSpy);

    const {unmount} = render(<WebspaceTabs route={route} router={router}>{() => null}</WebspaceTabs>);

    expect(router.bind).toBeCalledWith('webspace', expect.any(Object));
    expect(router.addUpdateRouteHook).toBeCalledWith(expect.any(Function));

    unmount();
    expect(bindWebspaceToRouterDisposerSpy).toBeCalledWith();
});

test('Save and update webspace when select value is changed', async() => {
    const user = userEvent.setup();
    const router = new Router({});
    const route = createRoute();

    mockedWebspaceStore.grantedWebspaces = [{key: 'sulu', name: 'Sulu'}, {key: 'sulu_blog', name: 'Sulu Blog'}];
    const webspace1 = {key: 'sulu', localizations: [{locale: 'en', default: true}]};
    const webspace2 = {
        key: 'sulu_blog',
        localizations: [{locale: 'en', default: false}, {locale: 'de', default: true}],
    };

    webspaceStore.getWebspace.mockImplementation((key) => {
        if (key === 'sulu') {
            return webspace1;
        }

        if (key === 'sulu_blog') {
            return webspace2;
        }
    });

    router.bind.mockImplementation((key, webspaceKey) => {
        if (key === 'webspace') {
            webspaceKey.set('sulu');
        }
    });

    render(<WebspaceTabs route={route} router={router}>{() => null}</WebspaceTabs>);

    await user.click(screen.getByRole('button', {name: /Sulu/}));
    await user.click(screen.getByRole('button', {name: 'Sulu Blog'}));

    await waitFor(() => {
        const latestTabsProps = getLatestMockProps(tabsMock);
        expect(latestTabsProps.childrenProps).toEqual(expect.objectContaining({webspace: webspace2}));
    });

    await user.click(screen.getByRole('button', {name: /Sulu Blog/}));
    await user.click(screen.getByRole('button', {name: 'Sulu'}));

    await waitFor(() => {
        const latestTabsProps = getLatestMockProps(tabsMock);
        expect(latestTabsProps.childrenProps).toEqual(expect.objectContaining({webspace: webspace1}));
    });

    expect(userStore.setPersistentSetting).toBeCalledWith('sulu_page.webspace_tabs.webspace', 'sulu');
    expect(screen.getByRole('button', {name: /Sulu/})).toBeInTheDocument();
});
