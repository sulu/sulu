// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import {SingleListOverlay} from 'sulu-admin-bundle/containers';
import {Router} from 'sulu-admin-bundle/services';
import SnippetAreaStore from '../stores/SnippetAreaStore';
import SnippetAreas from '../SnippetAreas';

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));
jest.mock('sulu-admin-bundle/services/Router', () => jest.fn());
jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) =>key),
}));
jest.mock('../stores/SnippetAreaStore', () => jest.fn());

test('Render snippet areas with loading icon', () => {
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    // $FlowFixMe
    const route = {};

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.loading = true;
    });

    expect(render(<SnippetAreas route={route} router={router} />)).toMatchSnapshot();
    expect(SnippetAreaStore).toBeCalledWith('sulu');
});

test('Render snippet areas with data as table', () => {
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    // $FlowFixMe
    const route = {};

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'default',
                title: 'Default',
            },
            footer: {
                defaultTitle: 'Footer Snippet',
                defaultUuid: 'some-other-uuid',
                key: 'footer',
                title: 'Footer',
            },
        };
    });

    expect(render(<SnippetAreas route={route} router={router} />)).toMatchSnapshot();
    expect(SnippetAreaStore).toBeCalledWith('sulu');
});

test('Close after clicking add without choosing a snippet', () => {
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    // $FlowFixMe
    const route = {};

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'default',
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const snippetAreas = mount(<SnippetAreas route={route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(false);
    snippetAreas.find('Button[className="addButton"] button').simulate('click');
    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(true);

    snippetAreas.find(SingleListOverlay).prop('onClose')();
    snippetAreas.update();
    expect(snippetAreas.find(SingleListOverlay).prop('open')).toEqual(false);

    expect(snippetAreaStore.save).not.toBeCalled();
});

test('Save after adding a new snippet area', () => {
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    // $FlowFixMe
    const route = {};

    const savePromise = Promise.resolve();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'default',
                title: 'Default',
            },
        };

        this.save = jest.fn().mockReturnValue(savePromise);
    });

    const snippetAreas = mount(<SnippetAreas route={route} router={router} />);
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
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    // $FlowFixMe
    const route = {};

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const snippetAreas = mount(<SnippetAreas route={route} router={router} />);
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
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    // $FlowFixMe
    const route = {};

    const deletePromise = Promise.resolve();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        };

        this.delete = jest.fn().mockReturnValue(deletePromise);
    });

    const snippetAreas = mount(<SnippetAreas route={route} router={router} />);
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
