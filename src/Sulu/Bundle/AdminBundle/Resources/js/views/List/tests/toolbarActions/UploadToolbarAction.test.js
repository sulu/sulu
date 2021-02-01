// @flow
import {render} from 'enzyme';
import {observable} from 'mobx';
import {act} from 'react-dom/test-utils';
import SymfonyRouting from 'fos-jsrouting/router';
import ListStore from '../../../../containers/List/stores/ListStore';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import List from '../../../../views/List';
import UploadToolbarAction from '../../toolbarActions/UploadToolbarAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../containers/List/stores/ListStore', () => jest.fn(function() {
    this.reload = jest.fn();
}));

jest.mock('../../../../views/List/List', () => jest.fn(function() {
    this.errors = [];
}));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.attributes = {
        locale: 'en',
    };
}));

function createUploadToolbarAction(options = {}) {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new UploadToolbarAction(listStore, list, router, locales, resourceStore, options);
}

test('Should correctly render node', () => {
    const uploadToolbarAction = createUploadToolbarAction({
        label: 'foo',
        icon: 'su-times',
        routeName: 'foo',
        requestParameters: {
            foo: 'bar',
            baz: 'foo',
        },
        routerAttributesToRequest: {
            '0': 'locale',
            'locale': 'locale2',
        },
        errorCodeMapping: {
            '400': 'sulu_admin.bad_request',
        },
        accept: ['text/csv'],
        minSize: 1000,
        maxSize: 9999,
        multiple: false,
    });

    expect(render(uploadToolbarAction.getNode())).toMatchSnapshot();
});

test('Should return config for toolbar item', () => {
    const uploadToolbarAction = createUploadToolbarAction();

    expect(uploadToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-upload',
        label: 'sulu_admin.upload',
        type: 'button',
    }));
});

test('Should return custom config for toolbar item', () => {
    const uploadToolbarAction = createUploadToolbarAction({
        label: 'foo',
        icon: 'bar',
    });

    expect(uploadToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'bar',
        label: 'foo',
        type: 'button',
    }));
});

test('Should make xhr request on confirm', () => {
    const promise = Promise.resolve({
        status: 200,
        statusText: '',
        ok: true,
    });

    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() => promise);

    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const uploadToolbarAction = createUploadToolbarAction({
        routeName: 'foo',
        requestParameters: {
            foo: 'bar',
            baz: 'foo',
        },
        routerAttributesToRequest: {
            '0': 'locale',
            'locale': 'locale2',
        },
    });

    const toolbarItemConfig = uploadToolbarAction.getToolbarItemConfig();
    act(() => {
        toolbarItemConfig.onClick();
    });

    uploadToolbarAction.handleConfirm([new File(['foo'], 'foo.jpg')]);

    expect(fetch).toBeCalledWith('foo?locale=en&locale2=en&foo=bar&baz=foo', expect.objectContaining({
        method: 'POST',
        body: expect.any(FormData),
    }));

    return promise.then(() => {
        expect(uploadToolbarAction.listStore.reload).toBeCalled();
    });
});

test('Should display errors if dropzone error occurs', () => {
    const uploadToolbarAction = createUploadToolbarAction({
        routeName: 'foo',
        multiple: true,
        minSize: 3000,
        maxSize: 4000,
    });

    const toolbarItemConfig = uploadToolbarAction.getToolbarItemConfig();
    act(() => {
        toolbarItemConfig.onClick();
    });

    uploadToolbarAction.handleError([
        {
            file: {
                name: 'file-invalid-type.jpg',
            },
            errors: [
                {
                    code: 'file-invalid-type',
                },
                {
                    code: 'too-many-files',
                },
            ],
        },
        {
            file: {
                name: 'file-too-large.jpg',
            },
            errors: [
                {
                    code: 'file-too-large',
                },
                {
                    code: 'too-many-files',
                },
            ],
        },
        {
            file: {
                name: 'file-too-small.jpg',
            },
            errors: [
                {
                    code: 'file-too-small',
                },
                {
                    code: 'too-many-files',
                },
            ],
        },
        {
            file: {
                name: 'not-existing-code.jpg',
            },
            errors: [
                {
                    code: 'not-existing-code',
                },
                {
                    code: 'too-many-files',
                },
            ],
        },
    ]);

    expect(uploadToolbarAction.errors).toEqual([
        'sulu_admin.dropzone_error_file-invalid-type',
        'sulu_admin.dropzone_error_file-too-large',
        'sulu_admin.dropzone_error_file-too-small',
        'sulu_admin.unexpected_upload_error',
        'sulu_admin.dropzone_error_too-many-files',
    ]);

    expect(uploadToolbarAction.list.errors).toEqual([
        'sulu_admin.dropzone_error_file-invalid-type',
        'sulu_admin.dropzone_error_file-too-large',
        'sulu_admin.dropzone_error_file-too-small',
        'sulu_admin.unexpected_upload_error',
        'sulu_admin.dropzone_error_too-many-files',
    ]);

    uploadToolbarAction.setDropzoneRef({open: jest.fn()});
    uploadToolbarAction.handleClick();

    expect(uploadToolbarAction.errors).toEqual([]);
    expect(uploadToolbarAction.list.errors).toEqual([]);
});

test('Should display error if server error occurs', () => {
    const promise = Promise.resolve({
        status: 400,
        statusText: '',
        ok: false,
    });

    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() => promise);

    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const uploadToolbarAction = createUploadToolbarAction({
        routeName: 'foo',
    });

    const toolbarItemConfig = uploadToolbarAction.getToolbarItemConfig();
    act(() => {
        toolbarItemConfig.onClick();
    });

    uploadToolbarAction.handleConfirm([new File(['foo'], 'foo.jpg')]);

    expect(fetch).toBeCalledWith('foo?', expect.objectContaining({
        method: 'POST',
        body: expect.any(FormData),
    }));

    return promise.then(() => {
        expect(uploadToolbarAction.errors).toEqual([
            'sulu_admin.unexpected_upload_error',
        ]);

        expect(uploadToolbarAction.list.errors).toEqual([
            'sulu_admin.unexpected_upload_error',
        ]);

        uploadToolbarAction.setDropzoneRef({open: jest.fn()});
        uploadToolbarAction.handleClick();

        expect(uploadToolbarAction.errors).toEqual([]);
        expect(uploadToolbarAction.list.errors).toEqual([]);
    });
});

test('Should display custom error if server error occurs', () => {
    const promise = Promise.resolve({
        status: 400,
        statusText: '',
        ok: false,
    });

    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() => promise);

    SymfonyRouting.generate.mockImplementation((routeName, params) => {
        return routeName + '?' + Object.keys(params).map((key) => key + '=' + params[key]).join('&');
    });

    const uploadToolbarAction = createUploadToolbarAction({
        routeName: 'foo',
        errorCodeMapping: {
            '400': 'sulu_admin.bad_request',
        },
    });

    const toolbarItemConfig = uploadToolbarAction.getToolbarItemConfig();
    act(() => {
        toolbarItemConfig.onClick();
    });

    uploadToolbarAction.handleConfirm([new File(['foo'], 'foo.jpg')]);

    expect(fetch).toBeCalledWith('foo?', expect.objectContaining({
        method: 'POST',
        body: expect.any(FormData),
    }));

    return promise.then(() => {
        expect(uploadToolbarAction.errors).toEqual([
            'sulu_admin.bad_request',
        ]);

        expect(uploadToolbarAction.list.errors).toEqual([
            'sulu_admin.bad_request',
        ]);

        uploadToolbarAction.setDropzoneRef({open: jest.fn()});
        uploadToolbarAction.handleClick();

        expect(uploadToolbarAction.errors).toEqual([]);
        expect(uploadToolbarAction.list.errors).toEqual([]);
    });
});
