// @flow
import {mount} from 'enzyme';
import React from 'react';
import DeleteDependantResourcesDialog from '../DeleteDependantResourcesDialog';
import ResourceRequester from '../../../services/ResourceRequester';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

class RequestPromise<T> extends Promise<T> {
    abort = jest.fn();

    static resolve<T>(object: Promise<T> | T): RequestPromise<T> {
        const promise = (Promise.resolve(object): any);
        promise.abort = jest.fn();

        return promise;
    }

    static reject<T>(object: Promise<T> | T): RequestPromise<T> {
        const promise = (Promise.reject(object): any);
        promise.abort = jest.fn();

        return promise;
    }
}

jest.mock('../../../services/ResourceRequester', () => ({
    delete: jest.fn(),
}));

beforeEach(() => {
    ResourceRequester.delete.mockReset();
});

const flushPromises = () => new Promise((resolve) => setTimeout(resolve));

function createDeferred() {
    let resolve: (value: any) => void = () => {};
    let reject: (error: any) => void = () => {};
    const promise = new RequestPromise((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });
    promise.abort = jest.fn();

    return {promise, resolve, reject};
}

const dependantResourceBatches = [
    [
        {id: 4, resourceKey: 'media'},
    ],
    [
        {id: 3, resourceKey: 'collections'},
        {id: 2, resourceKey: 'media'},
        {id: 3, resourceKey: 'media'},
    ],
    [
        {id: 2, resourceKey: 'collections'},
        {id: 1, resourceKey: 'media'},
    ],
];

const requestOptions = {
    foo: 'bar',
    locale: 'de',
};

function mountDialog(onCancel, onError, onFinish) {
    return mount(
        <DeleteDependantResourcesDialog
            dependantResourcesData={{
                dependantResourceBatches,
                dependantResourcesCount: 6,
                detail: 'Detail',
                title: 'Title',
            }}
            onCancel={onCancel}
            onError={onError}
            onFinish={onFinish}
            requestOptions={requestOptions}
        />
    );
}

function createReferencingResourcesResponse(id, title, referencingId = 'page-' + id) {
    const data = {
        code: 1106,
        resource: {id, resourceKey: 'media'},
        referencingResources: [{id: referencingId, resourceKey: 'pages', title}],
        referencingResourcesCount: 1,
    };

    return {
        status: 409,
        clone: () => ({json: () => Promise.resolve(data)}),
        json: () => Promise.resolve(data),
    };
}

test('The component should render', () => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const dependantResourceBatches = [
        [
            {id: 4, resourceKey: 'media'},
        ],
        [
            {id: 3, resourceKey: 'collections'},
            {id: 2, resourceKey: 'media'},
            {id: 3, resourceKey: 'media'},
        ],
        [
            {id: 2, resourceKey: 'collections'},
            {id: 1, resourceKey: 'media'},
        ],
    ];

    const dependantResourcesCount = 6;
    const dependantResourcesData = {
        dependantResourceBatches,
        dependantResourcesCount,
        detail: 'Detail',
        title: 'Title',
    };

    const requestOptions = {
        foo: 'bar',
        locale: 'de',
    };

    const view = mount(
        <DeleteDependantResourcesDialog
            dependantResourcesData={dependantResourcesData}
            onCancel={onCancel}
            onError={onError}
            onFinish={onFinish}
            requestOptions={requestOptions}
        />
    );

    expect(view.find('Dialog > Portal').at(0).render()).toMatchSnapshot();
});

test('The component should call cancel callback', () => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const dependantResourceBatches = [
        [
            {id: 4, resourceKey: 'media'},
        ],
        [
            {id: 3, resourceKey: 'collections'},
            {id: 2, resourceKey: 'media'},
            {id: 3, resourceKey: 'media'},
        ],
        [
            {id: 2, resourceKey: 'collections'},
            {id: 1, resourceKey: 'media'},
        ],
    ];

    const dependantResourcesCount = 6;
    const dependantResourcesData = {
        dependantResourceBatches,
        dependantResourcesCount,
        detail: 'Detail',
        title: 'Title',
    };

    const requestOptions = {
        foo: 'bar',
        locale: 'de',
    };

    const view = mount(
        <DeleteDependantResourcesDialog
            dependantResourcesData={dependantResourcesData}
            onCancel={onCancel}
            onError={onError}
            onFinish={onFinish}
            requestOptions={requestOptions}
        />
    );

    view.find('Button[skin="secondary"]').simulate('click');
    expect(onCancel).toHaveBeenCalled();
});

test('The component should delete dependant resources', async() => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const view = mountDialog(onCancel, onError, onFinish);

    const deferreds = [1, 2, 3, 4, 5, 6].map(createDeferred);
    deferreds.forEach(({promise}) => ResourceRequester.delete.mockReturnValueOnce(promise));

    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);
    view.find('Button[skin="primary"]').simulate('click');
    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(true);

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});
    expect(view.instance().totalDeletedResources).toBe(0);
    expect(view.instance().promises).toHaveLength(1);

    deferreds[0].resolve({});
    await flushPromises();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(4);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...requestOptions, id: 3});
    expect(view.instance().totalDeletedResources).toBe(1);
    expect(view.instance().promises).toHaveLength(3);

    deferreds[1].resolve({});
    deferreds[2].resolve({});
    deferreds[3].resolve({});
    await flushPromises();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(6);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(5, 'collections', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(6, 'media', {...requestOptions, id: 1});
    expect(view.instance().totalDeletedResources).toBe(4);
    expect(view.instance().promises).toHaveLength(2);

    deferreds[4].resolve({});
    deferreds[5].resolve({});
    await flushPromises();

    expect(view.instance().totalDeletedResources).toBe(6);
    view.update();
    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);

    expect(onFinish).toHaveBeenCalled();
    expect(onError).not.toHaveBeenCalled();
    expect(onCancel).not.toHaveBeenCalled();

    const cancelButton = view.find('Button[skin="secondary"]');
    expect(cancelButton.text()).toBe('sulu_admin.close');
    cancelButton.simulate('click');
    expect(onCancel).toHaveBeenCalled();
});

test('The component should ask before deleting referenced resources and delete them with force', async() => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const view = mountDialog(onCancel, onError, onFinish);

    ResourceRequester.delete
        .mockReturnValueOnce(RequestPromise.resolve({}))
        .mockReturnValueOnce(RequestPromise.resolve({}))
        .mockReturnValueOnce(RequestPromise.reject(createReferencingResourcesResponse(2, 'Page 1')))
        .mockReturnValueOnce(RequestPromise.reject(createReferencingResourcesResponse(3, 'Page 2')))
        .mockReturnValueOnce(RequestPromise.resolve({}))
        .mockReturnValueOnce(RequestPromise.resolve({}))
        .mockReturnValueOnce(RequestPromise.resolve({}))
        .mockReturnValueOnce(RequestPromise.resolve({}));

    view.find('Button[skin="primary"]').simulate('click');
    await flushPromises();
    view.update();

    // the run pauses and shows the references of both media of the second batch
    expect(ResourceRequester.delete).toHaveBeenCalledTimes(4);
    expect(view.instance().totalDeletedResources).toBe(2);
    expect(view.find('Dialog li').map((item) => item.text())).toEqual(['Page 1', 'Page 2']);
    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);
    expect(onError).not.toHaveBeenCalled();

    view.find('Button[skin="primary"]').simulate('click');
    await flushPromises();
    view.update();

    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(5, 'media', {...requestOptions, force: true, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(6, 'media', {...requestOptions, force: true, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(7, 'collections', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(8, 'media', {...requestOptions, id: 1});
    expect(view.instance().totalDeletedResources).toBe(6);
    expect(view.find('Dialog li')).toHaveLength(0);
    expect(onFinish).toHaveBeenCalled();
    expect(onError).not.toHaveBeenCalled();
});

test('The component should list a resource referencing multiple resources of a batch only once', async() => {
    const view = mountDialog(jest.fn(), jest.fn(), jest.fn());

    ResourceRequester.delete
        .mockReturnValueOnce(RequestPromise.resolve({}))
        .mockReturnValueOnce(RequestPromise.resolve({}))
        .mockReturnValueOnce(RequestPromise.reject(createReferencingResourcesResponse(2, 'Team', 'page-1')))
        .mockReturnValueOnce(RequestPromise.reject(createReferencingResourcesResponse(3, 'Team', 'page-1')));

    view.find('Button[skin="primary"]').simulate('click');
    await flushPromises();
    view.update();

    expect(view.find('Dialog li').map((item) => item.text())).toEqual(['Team']);
});

test('The component should stop when deleting referenced resources is cancelled', async() => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const view = mountDialog(onCancel, onError, onFinish);

    ResourceRequester.delete
        .mockReturnValueOnce(RequestPromise.reject(createReferencingResourcesResponse(4, 'Page 1')));

    view.find('Button[skin="primary"]').simulate('click');
    await flushPromises();
    view.update();

    expect(view.find('Dialog li').map((item) => item.text())).toEqual(['Page 1']);

    view.find('Button[skin="secondary"]').simulate('click');
    await flushPromises();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(onCancel).toHaveBeenCalled();
    expect(onFinish).not.toHaveBeenCalled();
    expect(onError).not.toHaveBeenCalled();
});
test('The component should reset itself when dependantResourcesData prop has changed', () => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const dependantResourcesData = {
        dependantResourceBatches: [
            [
                {id: 1, resourceKey: 'media'},
            ],
        ],
        dependantResourcesCount: 1,
        detail: 'Detail',
        title: 'Title',
    };

    const requestOptions = {
        foo: 'bar',
        locale: 'de',
    };

    const view = mount(
        <DeleteDependantResourcesDialog
            dependantResourcesData={dependantResourcesData}
            onCancel={onCancel}
            onError={onError}
            onFinish={onFinish}
            requestOptions={requestOptions}
        />
    );

    ResourceRequester.delete
        .mockReturnValueOnce(RequestPromise.resolve({}));

    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);
    view.find('Button[skin="primary"]').simulate('click');
    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(true);

    const newDependantResourcesData = {
        dependantResourceBatches: [],
        dependantResourcesCount: 0,
    };

    const promise = new Promise((resolve) => {
        view.setProps({...view.props(), dependantResourcesData: newDependantResourcesData}, () => {
            resolve(true);
        });
    });

    return promise.then(() => {
        view.update();
        expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);
    });
});

test('The component should reset itself when requestOptions prop has changed', () => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const dependantResourcesData = {
        dependantResourceBatches: [
            [
                {id: 1, resourceKey: 'media'},
            ],
        ],
        dependantResourcesCount: 1,
        detail: 'Detail',
        title: 'Title',
    };

    const requestOptions = {
        foo: 'bar',
        locale: 'de',
    };

    const view = mount(
        <DeleteDependantResourcesDialog
            dependantResourcesData={dependantResourcesData}
            onCancel={onCancel}
            onError={onError}
            onFinish={onFinish}
            requestOptions={requestOptions}
        />
    );

    ResourceRequester.delete
        .mockReturnValueOnce(RequestPromise.resolve({}));

    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);
    view.find('Button[skin="primary"]').simulate('click');
    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(true);

    const newRequestOptions = {
        locale: 'en',
    };

    const promise = new Promise((resolve) => {
        view.setProps({...view.props(), requestOptions: newRequestOptions}, () => {
            resolve(true);
        });
    });

    return promise.then(() => {
        view.update();
        expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);
    });
});

test('The component should call error callback', async() => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const view = mountDialog(onCancel, onError, onFinish);

    const deferreds = [1, 2, 3, 4].map(createDeferred);
    deferreds.forEach(({promise}) => ResourceRequester.delete.mockReturnValueOnce(promise));

    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);
    view.find('Button[skin="primary"]').simulate('click');
    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(true);

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});
    expect(view.instance().totalDeletedResources).toBe(0);
    expect(view.instance().promises).toHaveLength(1);

    deferreds[0].resolve({});
    await flushPromises();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(4);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...requestOptions, id: 3});
    expect(view.instance().totalDeletedResources).toBe(1);
    expect(view.instance().promises).toHaveLength(3);

    deferreds[1].resolve({});
    deferreds[2].reject({
        json: () => Promise.resolve({message: 'Something really bad happened'}),
    });
    deferreds[3].resolve({});
    await flushPromises();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(4);
    expect(view.instance().totalDeletedResources).toBe(3);

    view.update();
    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);

    expect(onError).toHaveBeenCalled();
    expect(onFinish).not.toHaveBeenCalled();
    expect(onCancel).not.toHaveBeenCalled();

    const cancelButton = view.find('Button[skin="secondary"]');
    expect(cancelButton.text()).toBe('sulu_admin.close');
    cancelButton.simulate('click');
    expect(onCancel).toHaveBeenCalled();
});
test('The component should abort requests on cancel', async() => {
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();

    const view = mountDialog(onCancel, onError, onFinish);

    const deferreds = [1, 2, 3, 4, 5, 6].map(createDeferred);
    deferreds.forEach(({promise}) => ResourceRequester.delete.mockReturnValueOnce(promise));

    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(false);
    view.find('Button[skin="primary"]').simulate('click');
    expect(view.find('Button[skin="primary"]').prop('loading')).toBe(true);

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});
    expect(view.instance().totalDeletedResources).toBe(0);
    expect(view.instance().promises).toHaveLength(1);

    deferreds[0].resolve({});
    deferreds[2].resolve({});
    deferreds[3].resolve({});
    await flushPromises();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(4);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...requestOptions, id: 3});
    expect(view.instance().totalDeletedResources).toBe(3);
    expect(view.instance().promises).toHaveLength(3);

    const cancelButton = view.find('Button[skin="secondary"]');
    expect(cancelButton.text()).toBe('sulu_admin.cancel');
    cancelButton.simulate('click');

    const [promise1, promise2, promise3, promise4, promise5, promise6] = deferreds.map(({promise}) => promise);
    expect(promise1.abort).not.toHaveBeenCalled();
    expect(promise2.abort).toHaveBeenCalled();
    expect(promise3.abort).toHaveBeenCalled();
    expect(promise4.abort).toHaveBeenCalled();
    expect(promise5.abort).not.toHaveBeenCalled();
    expect(promise6.abort).not.toHaveBeenCalled();

    expect(onCancel).toHaveBeenCalled();
    expect(onError).not.toHaveBeenCalled();
    expect(onFinish).not.toHaveBeenCalled();
});
