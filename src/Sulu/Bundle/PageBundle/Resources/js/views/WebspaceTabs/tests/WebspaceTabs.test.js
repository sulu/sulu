// @flow
import React from 'react';
import {mount} from 'enzyme';
import {Router, Route} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import WebspaceTabs from '../WebspaceTabs';
import webspaceStore from '../../../stores/webspaceStore';

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.addUpdateRouteHook = jest.fn();
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

    const route = new Route({
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

    const webspaceTabs = mount(
        <WebspaceTabs isRootView={true} route={route} router={router}>
            {(props) => <h1>{props && props.webspace && props.webspace.key}</h1>}
        </WebspaceTabs>
    );

    webspaceTabs.instance().webspaceKey.set('sulu_blog');
    expect(webspaceTabs.children().render()).toMatchSnapshot();
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

    const route = new Route({
        name: 'webspace_tabs',
        path: '/webspace_tabs',
        type: 'webspace_tabs',
    });

    const bindWebspaceToRouterDisposerSpy = jest.fn();
    router.addUpdateRouteHook.mockImplementationOnce(() => bindWebspaceToRouterDisposerSpy);
    const webspaceTabs = mount(<WebspaceTabs route={route} router={router}>{() => null}</WebspaceTabs>);

    expect(router.bind).toBeCalledWith('webspace', webspaceTabs.instance().webspaceKey);
    expect(router.addUpdateRouteHook).toBeCalledWith(webspaceTabs.instance().bindWebspaceToRouter);

    const webspaceDisposer = jest.fn();

    webspaceTabs.instance().webspaceDisposer = webspaceDisposer;

    webspaceTabs.unmount();
    expect(bindWebspaceToRouterDisposerSpy).toBeCalledWith();
    expect(webspaceDisposer).toBeCalledWith();
});

test('Save and update webspace when select value is changed', () => {
    const router = new Router({});

    const route = new Route({
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

    const webspaceTabs = mount(<WebspaceTabs route={route} router={router}>{() => null}</WebspaceTabs>);
    webspaceTabs.instance().webspaceKey.set('sulu_blog');

    webspaceTabs.update();
    expect(webspaceTabs.find('WebspaceSelect').prop('value')).toEqual('sulu_blog');
    expect(webspaceTabs.find('Tabs').at(0).prop('childrenProps'))
        .toEqual(expect.objectContaining({webspace: webspace2}));
    webspaceTabs.find('WebspaceSelect').prop('onChange')('sulu');

    webspaceTabs.update();
    expect(userStore.setPersistentSetting).toBeCalledWith('sulu_page.webspace_tabs.webspace', 'sulu');
    expect(webspaceTabs.find('Tabs').at(0).prop('childrenProps'))
        .toEqual(expect.objectContaining({webspace: webspace1}));
    expect(webspaceTabs.find('WebspaceSelect').prop('value')).toEqual('sulu');
});
