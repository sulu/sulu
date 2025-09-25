// @flow
import React from 'react';
import {shallow, mount, render} from 'enzyme';
import {Route, Router} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleListOverlay: jest.fn(() => null),
    withToolbar: jest.fn((Component) => Component),
}));
jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) =>key),
}));
jest.mock('../stores/SnippetAreaStore', () => jest.fn());

jest.mock('sulu-website-bundle/containers/CacheClearToolbarAction', () => jest.fn(function() {
    this.getNode = jest.fn();
    this.getToolbarItemConfig = jest.fn();
}));
jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.attributes = {
        webspace: 'sulu',
    };
}));

beforeEach(() => {
    jest.resetModules();
});

test('Show loader when loading snippet areas', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = new Router();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.loading = true;
    });

    const snippetAreas = shallow(<SnippetAreas route={router.route} router={router} />);
    expect(snippetAreas.find('Loader')).toHaveLength(1);
});

test('Render snippet areas with data as table', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = new Router();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: null,
                snippetUuid: null,
                key: 'default',
                title: 'Default',
            },
            footer: {
                snippetTitle: 'Footer Snippet',
                snippetUuid: 'some-other-uuid',
                key: 'footer',
                title: 'Footer',
            },
        };
    });

    expect(render(<SnippetAreas route={router.route} router={router} />)).toMatchSnapshot();
    expect(SnippetAreaStore).toBeCalledWith('sulu');
});

test('Close after clicking add without choosing a snippet', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;

    const router = new Router();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: null,
                snippetUuid: null,
                key: 'default',
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const snippetAreas = mount(<SnippetAreas route={router.route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(false);
    snippetAreas.find('Button[className="addButton"] button').simulate('click');
    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(true);
    expect(snippetAreas.find(SingleListOverlay).prop('options')).toEqual({areas: 'default'});

    snippetAreas.find(SingleListOverlay).prop('onClose')();
    snippetAreas.update();
    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(false);

    expect(snippetAreaStore.save).not.toBeCalled();
});

test('Save after adding a new snippet area', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;

    const router = new Router();

    const savePromise = Promise.resolve();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: null,
                snippetUuid: null,
                key: 'default',
                title: 'Default',
            },
        };

        this.save = jest.fn().mockReturnValue(savePromise);
    });

    const snippetAreas = mount(<SnippetAreas route={router.route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(false);
    snippetAreas.find('Button[className="addButton"] button').simulate('click');
    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(true);
    snippetAreas.find(SingleListOverlay).prop('onConfirm')({id: 'some-uuid'});
    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(true);

    expect(snippetAreaStore.save).toBeCalledWith('default', 'some-uuid');

    return savePromise.then(() => {
        snippetAreas.update();
        expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(false);
    });
});

test('Close after clicking delete and cancel dialog', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = new Router();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: 'Default Snippet',
                snippetUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const snippetAreas = mount(<SnippetAreas route={router.route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(snippetAreas.find('Dialog').prop('open')).toEqual(false);
    snippetAreas.find('Button[className="deleteButton"] button').simulate('click');
    expect(snippetAreas.find('Dialog').prop('open')).toEqual(true);

    snippetAreas.find('Dialog').prop('onCancel')();
    snippetAreas.update();
    expect(snippetAreas.find('Dialog').prop('open')).toEqual(false);

    expect(snippetAreaStore.save).not.toBeCalled();
});

test('Delete after confirming the confirmation dialog', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = new Router();

    const deletePromise = Promise.resolve();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: 'Default Snippet',
                snippetUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        };

        this.delete = jest.fn().mockReturnValue(deletePromise);
    });

    const snippetAreas = mount(<SnippetAreas route={router.route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(snippetAreas.find('Dialog').prop('open')).toEqual(false);
    snippetAreas.find('Button[className="deleteButton"] button').simulate('click');
    expect(snippetAreas.find('Dialog').prop('open')).toEqual(true);
    snippetAreas.find('Dialog').prop('onConfirm')();
    expect(snippetAreas.find('Dialog').prop('open')).toEqual(true);

    expect(snippetAreaStore.delete).toBeCalledWith('default');

    return deletePromise.then(() => {
        snippetAreas.update();
        expect(snippetAreas.find('Dialog').prop('open')).toEqual(false);
    });
});

test('Navigate when selected default snippet is clicked', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const route = new Route({
        name: 'snippet_areas',
        path: '/snippet-areas',
        type: 'snippet_areas',
        options: {
            snippetEditView: 'sulu_snippet.edit_form',
        },
    });
    const router = new Router();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: 'Default Snippet',
                snippetUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const snippetAreas = mount(<SnippetAreas route={route} router={router} />);
    snippetAreas.find('Button[className="titleButton"] button').simulate('click');

    expect(router.navigate).toBeCalledWith('sulu_snippet.edit_form', {id: 'some-uuid'});
});

test('Should use CacheClearToolbarAction for cache clearing', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');
    const toolbarFunction = findWithHighOrderFunction(withToolbar, SnippetAreas);
    const CacheClearToolbarAction = require('sulu-website-bundle/containers').CacheClearToolbarAction;

    const router = new Router();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: 'Default Snippet',
                snippetUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        };
    });

    const snippetAreas = mount(
        <SnippetAreas route={router.route} router={router} />
    );

    const cacheClearToolbarAction: CacheClearToolbarAction = (CacheClearToolbarAction: any).mock.instances[0];

    expect(cacheClearToolbarAction.getNode).toBeCalledWith();

    expect(cacheClearToolbarAction.getToolbarItemConfig).not.toBeCalled();
    toolbarFunction.call(snippetAreas.instance());
    expect(cacheClearToolbarAction.getToolbarItemConfig).toBeCalled();
});
