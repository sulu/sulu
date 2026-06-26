// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DeleteDependantResourcesDialog from '../DeleteDependantResourcesDialog';
import ResourceRequester from '../../../services/ResourceRequester';
import type {DependantResourcesData} from '../../../types';

jest.mock('../../../utils/Translator');

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

type DeferredRequestPromise = {|
    promise: RequestPromise<any>,
    reject: (error: any) => void,
    resolve: (value: any) => void,
|};

function getDeleteMock() {
    return (ResourceRequester.delete: any);
}

function createDependantResourcesData(): DependantResourcesData {
    return {
        dependantResourceBatches: [
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
        ],
        dependantResourcesCount: 6,
        detail: 'Detail',
        title: 'Title',
    };
}

function createRequestOptions() {
    return {
        foo: 'bar',
        locale: 'de',
    };
}

function createProps(props: Object = {}) {
    return {
        dependantResourcesData: createDependantResourcesData(),
        onCancel: jest.fn(),
        onError: jest.fn(),
        onFinish: jest.fn(),
        requestOptions: createRequestOptions(),
        ...props,
    };
}

function createDeferredRequestPromise(): DeferredRequestPromise {
    let rejectPromise = jest.fn();
    let resolvePromise = jest.fn();
    const promise = new RequestPromise((resolve, reject) => {
        rejectPromise = reject;
        resolvePromise = resolve;
    });

    return {
        promise,
        reject: rejectPromise,
        resolve: resolvePromise,
    };
}

async function resolveRequests(requests: Array<DeferredRequestPromise>) {
    await act(async() => {
        requests.forEach((request) => request.resolve({}));
        await Promise.all(requests.map((request) => request.promise));
    });
}

async function rejectRequest(request: DeferredRequestPromise, error: any) {
    await act(async() => {
        request.reject(error);
        await request.promise.catch(() => undefined);
    });
}

beforeEach(() => {
    getDeleteMock().mockReset();
});

test('The component should render', () => {
    const {baseElement} = render(<DeleteDependantResourcesDialog {...createProps()} />);

    expect(baseElement).toMatchSnapshot();
});

test('The component should call cancel callback', async() => {
    const user = userEvent.setup();
    const props = createProps();

    render(<DeleteDependantResourcesDialog {...props} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(props.onCancel).toHaveBeenCalled();
});

test('The component should delete dependant resources', async() => {
    const user = userEvent.setup();
    const props = createProps();
    const requestOptions = props.requestOptions;
    const request1 = createDeferredRequestPromise();
    const request2 = createDeferredRequestPromise();
    const request3 = createDeferredRequestPromise();
    const request4 = createDeferredRequestPromise();
    const request5 = createDeferredRequestPromise();
    const request6 = createDeferredRequestPromise();

    getDeleteMock()
        .mockReturnValueOnce(request1.promise)
        .mockReturnValueOnce(request2.promise)
        .mockReturnValueOnce(request3.promise)
        .mockReturnValueOnce(request4.promise)
        .mockReturnValueOnce(request5.promise)
        .mockReturnValueOnce(request6.promise);

    render(<DeleteDependantResourcesDialog {...props} />);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});

    await resolveRequests([request1]);
    await waitFor(() => expect(ResourceRequester.delete).toHaveBeenCalledTimes(4));

    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...requestOptions, id: 3});

    await resolveRequests([request2, request3, request4]);
    await waitFor(() => expect(ResourceRequester.delete).toHaveBeenCalledTimes(6));

    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(5, 'collections', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(6, 'media', {...requestOptions, id: 1});

    await resolveRequests([request5, request6]);
    await waitFor(() => expect(props.onFinish).toHaveBeenCalled());

    expect(props.onError).not.toHaveBeenCalled();
    expect(props.onCancel).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.close'}));

    expect(props.onCancel).toHaveBeenCalled();
});

test('The component should reset itself when dependantResourcesData prop has changed', async() => {
    const user = userEvent.setup();
    const props = createProps({
        dependantResourcesData: {
            dependantResourceBatches: [
                [
                    {id: 1, resourceKey: 'media'},
                ],
            ],
            dependantResourcesCount: 1,
            detail: 'Detail',
            title: 'Title',
        },
    });
    const newProps = {
        ...props,
        dependantResourcesData: {
            dependantResourceBatches: [],
            dependantResourcesCount: 0,
            detail: 'New Detail',
            title: 'New Title',
        },
    };

    getDeleteMock().mockReturnValueOnce(new RequestPromise(() => {}));

    const {rerender} = render(<DeleteDependantResourcesDialog {...props} />);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();

    rerender(<DeleteDependantResourcesDialog {...newProps} />);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
});

test('The component should reset itself when requestOptions prop has changed', async() => {
    const user = userEvent.setup();
    const props = createProps({
        dependantResourcesData: {
            dependantResourceBatches: [
                [
                    {id: 1, resourceKey: 'media'},
                ],
            ],
            dependantResourcesCount: 1,
            detail: 'Detail',
            title: 'Title',
        },
    });
    const newProps = {
        ...props,
        requestOptions: {
            locale: 'en',
        },
    };

    getDeleteMock().mockReturnValueOnce(new RequestPromise(() => {}));

    const {rerender} = render(<DeleteDependantResourcesDialog {...props} />);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();

    rerender(<DeleteDependantResourcesDialog {...newProps} />);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
});

test('The component should call error callback', async() => {
    const user = userEvent.setup();
    const props = createProps();
    const requestOptions = props.requestOptions;
    const request1 = createDeferredRequestPromise();
    const request2 = createDeferredRequestPromise();
    const request3 = createDeferredRequestPromise();
    const request4 = createDeferredRequestPromise();

    getDeleteMock()
        .mockReturnValueOnce(request1.promise)
        .mockReturnValueOnce(request2.promise)
        .mockReturnValueOnce(request3.promise)
        .mockReturnValueOnce(request4.promise);

    render(<DeleteDependantResourcesDialog {...props} />);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});

    await resolveRequests([request1]);
    await waitFor(() => expect(ResourceRequester.delete).toHaveBeenCalledTimes(4));

    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...requestOptions, id: 3});

    await resolveRequests([request2, request4]);
    await rejectRequest(request3, {
        json: () => Promise.resolve({message: 'Something really bad happened'}),
    });
    await waitFor(() => expect(props.onError).toHaveBeenCalled());

    expect(props.onFinish).not.toHaveBeenCalled();
    expect(props.onCancel).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.close'}));

    expect(props.onCancel).toHaveBeenCalled();
});

test('The component should abort requests on cancel', async() => {
    const user = userEvent.setup();
    const props = createProps();
    const requestOptions = props.requestOptions;
    const request1 = createDeferredRequestPromise();
    const request2 = createDeferredRequestPromise();
    const request3 = createDeferredRequestPromise();
    const request4 = createDeferredRequestPromise();
    const request5 = createDeferredRequestPromise();
    const request6 = createDeferredRequestPromise();

    getDeleteMock()
        .mockReturnValueOnce(request1.promise)
        .mockReturnValueOnce(request2.promise)
        .mockReturnValueOnce(request3.promise)
        .mockReturnValueOnce(request4.promise)
        .mockReturnValueOnce(request5.promise)
        .mockReturnValueOnce(request6.promise);

    render(<DeleteDependantResourcesDialog {...props} />);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();

    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});

    await resolveRequests([request1]);
    await waitFor(() => expect(ResourceRequester.delete).toHaveBeenCalledTimes(4));

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(request1.promise.abort).not.toHaveBeenCalled();
    expect(request2.promise.abort).toHaveBeenCalled();
    expect(request3.promise.abort).toHaveBeenCalled();
    expect(request4.promise.abort).toHaveBeenCalled();
    expect(request5.promise.abort).not.toHaveBeenCalled();
    expect(request6.promise.abort).not.toHaveBeenCalled();

    expect(props.onCancel).toHaveBeenCalled();
    expect(props.onError).not.toHaveBeenCalled();
    expect(props.onFinish).not.toHaveBeenCalled();
});
