// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Router} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import {createRoute, mockResizeObserver} from 'sulu-admin-bundle/utils/TestHelper';
import WebspaceTabs from '../WebspaceTabs';
import webspaceStore from '../../../stores/webspaceStore';

jest.mock('debounce', () => jest.fn((callback) => callback));

mockResizeObserver();

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.addUpdateRouteHook = jest.fn().mockReturnValue(jest.fn());
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

test('Render webspace select with children when webspaces are not loaded yet', () => {
    const router = new Router({});

    const route = createRoute({}, {}, [], {
        name: 'webspace_tabs',
        path: '/webspace_tabs',
        type: 'webspace_tabs',
    });

    const webspace = {key: 'sulu_blog', localizations: [{locale: 'en', default: false}, {locale: 'de', default: true}]};

    webspaceStore.getWebspace.mockImplementation((key) => {
        if (key === 'sulu_blog') {
            return webspace;
        }
    });

    const {container} = render(
        <WebspaceTabs isRootView={true} route={route} router={router}>
            {(props) => <h1>{props && props.webspace && props.webspace.key}</h1>}
        </WebspaceTabs>
    );

    const webspaceKey = router.bind.mock.calls[0][1];
    act(() => webspaceKey.set('sulu_blog'));

    expect(screen.getByRole('heading', {name: 'sulu_blog'})).toBeInTheDocument();
    expect(container).toMatchSnapshot();
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

test('Should bind router attributes and dispose the updateRouteHook and webspace persistence', () => {
    const router = new Router({});

    const route = createRoute({}, {}, [], {
        name: 'webspace_tabs',
        path: '/webspace_tabs',
        type: 'webspace_tabs',
    });

    const bindWebspaceToRouterDisposerSpy = jest.fn();
    router.addUpdateRouteHook.mockImplementationOnce(() => bindWebspaceToRouterDisposerSpy);
    const {unmount} = render(
        <WebspaceTabs route={route} router={router}>{() => null}</WebspaceTabs>
    );

    expect(router.bind).toHaveBeenCalledWith('webspace', expect.any(Object));
    expect(router.addUpdateRouteHook).toHaveBeenCalledWith(expect.any(Function));

    unmount();
    expect(bindWebspaceToRouterDisposerSpy).toHaveBeenCalledWith();

    const webspaceKey = router.bind.mock.calls[0][1];
    act(() => webspaceKey.set('sulu_blog'));
    expect(userStore.setPersistentSetting).not.toHaveBeenCalled();
});

test('Save and update webspace when select value is changed', async() => {
    const user = userEvent.setup();
    const router = new Router({});

    const route = createRoute({}, {}, [], {
        name: 'webspace_tabs',
        path: '/webspace_tabs',
        type: 'webspace_tabs',
    });

    const webspace1 = {key: 'sulu', name: 'Sulu', localizations: [{locale: 'en', default: true}]};
    const webspace2 = {
        name: 'Sulu Blog',
        key: 'sulu_blog',
        localizations: [{locale: 'en', default: false}, {locale: 'de', default: true}],
    };
    (webspaceStore: any).grantedWebspaces = [webspace1, webspace2];

    webspaceStore.getWebspace.mockImplementation((key) => {
        if (key === 'sulu') {
            return webspace1;
        }

        if (key === 'sulu_blog') {
            return webspace2;
        }
    });

    render(
        <WebspaceTabs route={route} router={router}>
            {(props) => <h1>{props && props.webspace && props.webspace.key}</h1>}
        </WebspaceTabs>
    );

    const webspaceKey = router.bind.mock.calls[0][1];
    act(() => webspaceKey.set('sulu_blog'));

    expect(screen.getByRole('button', {name: /Sulu Blog/})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'sulu_blog'})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /Sulu Blog/}));
    await user.click(screen.getByText('Sulu'));

    expect(userStore.setPersistentSetting).toHaveBeenCalledWith('sulu_page.webspace_tabs.webspace', 'sulu');
    expect(screen.getByRole('heading', {name: 'sulu'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /Sulu su-angle-down/})).toBeInTheDocument();
});
