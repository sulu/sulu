// @flow
import React from 'react';
import {Router} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import {createRoute, findElementByType, mockResizeObserver, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
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

    const {container, instance: webspaceTabs} = renderWithRef(
        <WebspaceTabs isRootView={true} route={route} router={router}>
            {(props) => <h1>{props && props.webspace && props.webspace.key}</h1>}
        </WebspaceTabs>
    );

    webspaceTabs.webspaceKey.set('sulu_blog');
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

test('Should bind and unbind router attributes and updateRouteHook', () => {
    const router = new Router({});

    const route = createRoute({}, {}, [], {
        name: 'webspace_tabs',
        path: '/webspace_tabs',
        type: 'webspace_tabs',
    });

    const bindWebspaceToRouterDisposerSpy = jest.fn();
    router.addUpdateRouteHook.mockImplementationOnce(() => bindWebspaceToRouterDisposerSpy);
    const {instance: webspaceTabs, unmount} = renderWithRef(
        <WebspaceTabs route={route} router={router}>{() => null}</WebspaceTabs>
    );

    expect(router.bind).toHaveBeenCalledWith('webspace', webspaceTabs.webspaceKey);
    expect(router.addUpdateRouteHook).toHaveBeenCalledWith(webspaceTabs.bindWebspaceToRouter);

    const webspaceDisposer = jest.fn();

    webspaceTabs.webspaceDisposer = webspaceDisposer;

    unmount();
    expect(bindWebspaceToRouterDisposerSpy).toHaveBeenCalledWith();
    expect(webspaceDisposer).toHaveBeenCalledWith();
});

test('Save and update webspace when select value is changed', () => {
    const router = new Router({});

    const route = createRoute({}, {}, [], {
        name: 'webspace_tabs',
        path: '/webspace_tabs',
        type: 'webspace_tabs',
    });

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

    const {instance: webspaceTabs} = renderWithRef(
        <WebspaceTabs route={route} router={router}>{() => null}</WebspaceTabs>
    );
    webspaceTabs.webspaceKey.set('sulu_blog');

    const getTabsProps = () => findElementByType(webspaceTabs.render(), 'Tabs').props;
    const getWebspaceSelectProps = () => findElementByType(getTabsProps().header, 'WebspaceSelect').props;

    expect(getWebspaceSelectProps().value).toEqual('sulu_blog');
    expect(getTabsProps().childrenProps)
        .toEqual(expect.objectContaining({webspace: webspace2}));
    getWebspaceSelectProps().onChange('sulu');

    expect(userStore.setPersistentSetting).toHaveBeenCalledWith('sulu_page.webspace_tabs.webspace', 'sulu');
    expect(getTabsProps().childrenProps)
        .toEqual(expect.objectContaining({webspace: webspace1}));
    expect(getWebspaceSelectProps().value).toEqual('sulu');
});
