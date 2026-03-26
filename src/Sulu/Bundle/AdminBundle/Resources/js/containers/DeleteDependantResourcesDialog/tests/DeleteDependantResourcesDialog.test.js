// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DeleteDependantResourcesDialog from '../DeleteDependantResourcesDialog';
import ResourceRequester from '../../../services/ResourceRequester';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../services/ResourceRequester', () => ({
    delete: jest.fn(),
}));

function getDeleteButton() {
    return screen.getByRole('button', {name: 'sulu_admin.delete'});
}

function getCancelButton() {
    return screen.getByRole('button', {name: 'sulu_admin.cancel'});
}

function getCloseButton() {
    return screen.getByRole('button', {name: 'sulu_admin.close'});
}

function createDeferredRequestPromise() {
    const deferred: any = {};

    deferred.promise = new Promise((resolve, reject) => {
        deferred.resolve = resolve;
        deferred.reject = reject;
    });
    deferred.promise.abort = jest.fn();

    return deferred;
}

const defaultDependantResourcesData = {
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

const defaultRequestOptions = {
    foo: 'bar',
    locale: 'de',
};

function renderDeleteDependantResourcesDialog(props: Object = {}) {
    return render(
        <DeleteDependantResourcesDialog
            dependantResourcesData={defaultDependantResourcesData}
            onCancel={jest.fn()}
            onError={jest.fn()}
            onFinish={jest.fn()}
            requestOptions={defaultRequestOptions}
            {...props}
        />
    );
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('The component should render', () => {
    renderDeleteDependantResourcesDialog();

    expect(document.body).toMatchSnapshot();
    expect(screen.getByText('Title')).toBeInTheDocument();
    expect(screen.getByText('Detail')).toBeInTheDocument();
    expect(getDeleteButton()).toBeEnabled();
    expect(getCancelButton()).toBeInTheDocument();
});

test('The component should call cancel callback', async() => {
    const user = userEvent.setup();
    const onCancel = jest.fn();
    renderDeleteDependantResourcesDialog({onCancel});

    expect(onCancel).not.toHaveBeenCalled();
    await user.click(getCancelButton());
    expect(onCancel).toHaveBeenCalled();
});

test('The component should delete dependant resources', async() => {
    const user = userEvent.setup();
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();
    renderDeleteDependantResourcesDialog({onCancel, onError, onFinish});

    const deferred1 = createDeferredRequestPromise();
    const deferred2 = createDeferredRequestPromise();
    const deferred3 = createDeferredRequestPromise();
    const deferred4 = createDeferredRequestPromise();
    const deferred5 = createDeferredRequestPromise();
    const deferred6 = createDeferredRequestPromise();

    ResourceRequester.delete
        .mockReturnValueOnce(deferred1.promise)
        .mockReturnValueOnce(deferred2.promise)
        .mockReturnValueOnce(deferred3.promise)
        .mockReturnValueOnce(deferred4.promise)
        .mockReturnValueOnce(deferred5.promise)
        .mockReturnValueOnce(deferred6.promise);

    expect(getDeleteButton()).toBeEnabled();
    await user.click(getDeleteButton());
    expect(getDeleteButton()).toBeDisabled();
    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...defaultRequestOptions, id: 4});
    expect(screen.getByRole('progressbar')).toHaveAttribute('max', '6');
    expect(screen.getByRole('progressbar')).toHaveValue(0);
    expect(screen.getByRole('progressbar')).toHaveClass('progress');

    deferred1.resolve({});

    await waitFor(() => {
        expect(ResourceRequester.delete).toHaveBeenCalledTimes(4);
    });
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...defaultRequestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...defaultRequestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...defaultRequestOptions, id: 3});
    expect(screen.getByRole('progressbar')).toHaveValue(1);

    deferred2.resolve({});
    deferred3.resolve({});
    deferred4.resolve({});

    await waitFor(() => {
        expect(ResourceRequester.delete).toHaveBeenCalledTimes(6);
    });
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(5, 'collections', {...defaultRequestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(6, 'media', {...defaultRequestOptions, id: 1});
    expect(screen.getByRole('progressbar')).toHaveValue(4);

    deferred5.resolve({});
    deferred6.resolve({});

    await waitFor(() => {
        expect(onFinish).toHaveBeenCalled();
    });

    expect(onError).not.toHaveBeenCalled();
    expect(onCancel).not.toHaveBeenCalled();
    expect(getCloseButton()).toBeInTheDocument();

    await user.click(getCloseButton());
    expect(onCancel).toHaveBeenCalled();
});

test('The component should reset itself when dependantResourcesData prop has changed', async() => {
    const user = userEvent.setup();
    const {rerender} = renderDeleteDependantResourcesDialog({
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

    const deferred = createDeferredRequestPromise();
    ResourceRequester.delete.mockReturnValueOnce(deferred.promise);

    expect(getDeleteButton()).toBeEnabled();
    await user.click(getDeleteButton());
    expect(getDeleteButton()).toBeDisabled();

    rerender(
        <DeleteDependantResourcesDialog
            dependantResourcesData={{
                dependantResourceBatches: [],
                dependantResourcesCount: 0,
                detail: '',
                title: '',
            }}
            onCancel={jest.fn()}
            onError={jest.fn()}
            onFinish={jest.fn()}
            requestOptions={defaultRequestOptions}
        />
    );

    expect(getDeleteButton()).toBeEnabled();
});

test('The component should reset itself when requestOptions prop has changed', async() => {
    const user = userEvent.setup();
    const {rerender} = renderDeleteDependantResourcesDialog({
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

    const deferred = createDeferredRequestPromise();
    ResourceRequester.delete.mockReturnValueOnce(deferred.promise);

    expect(getDeleteButton()).toBeEnabled();
    await user.click(getDeleteButton());
    expect(getDeleteButton()).toBeDisabled();

    rerender(
        <DeleteDependantResourcesDialog
            dependantResourcesData={{
                dependantResourceBatches: [
                    [
                        {id: 1, resourceKey: 'media'},
                    ],
                ],
                dependantResourcesCount: 1,
                detail: 'Detail',
                title: 'Title',
            }}
            onCancel={jest.fn()}
            onError={jest.fn()}
            onFinish={jest.fn()}
            requestOptions={{locale: 'en'}}
        />
    );

    expect(getDeleteButton()).toBeEnabled();
});

test('The component should call error callback', async() => {
    const user = userEvent.setup();
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();
    renderDeleteDependantResourcesDialog({onCancel, onError, onFinish});

    const deferred1 = createDeferredRequestPromise();
    const deferred2 = createDeferredRequestPromise();
    const deferred3 = createDeferredRequestPromise();
    const deferred4 = createDeferredRequestPromise();

    ResourceRequester.delete
        .mockReturnValueOnce(deferred1.promise)
        .mockReturnValueOnce(deferred2.promise)
        .mockReturnValueOnce(deferred3.promise)
        .mockReturnValueOnce(deferred4.promise);

    expect(getDeleteButton()).toBeEnabled();
    await user.click(getDeleteButton());
    expect(getDeleteButton()).toBeDisabled();
    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...defaultRequestOptions, id: 4});

    deferred1.resolve({});

    await waitFor(() => {
        expect(ResourceRequester.delete).toHaveBeenCalledTimes(4);
    });
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...defaultRequestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...defaultRequestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...defaultRequestOptions, id: 3});
    expect(screen.getByRole('progressbar')).toHaveValue(1);

    deferred2.resolve({});
    deferred4.resolve({});
    deferred3.reject({
        json: () => Promise.resolve({message: 'Something really bad happened'}),
    });

    await waitFor(() => {
        expect(onError).toHaveBeenCalled();
    });

    expect(screen.getByRole('progressbar')).toHaveAttribute('max', '6');
    expect(screen.getByRole('progressbar')).toHaveValue(4);
    expect(screen.getByRole('progressbar')).toHaveClass('error');
    expect(onFinish).not.toHaveBeenCalled();
    expect(onCancel).not.toHaveBeenCalled();
    expect(getCloseButton()).toBeInTheDocument();

    await user.click(getCloseButton());
    expect(onCancel).toHaveBeenCalled();
});

test('The component should abort requests on cancel', async() => {
    const user = userEvent.setup();
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();
    renderDeleteDependantResourcesDialog({onCancel, onError, onFinish});

    const deferred1 = createDeferredRequestPromise();
    const deferred2 = createDeferredRequestPromise();
    const deferred3 = createDeferredRequestPromise();
    const deferred4 = createDeferredRequestPromise();
    const deferred5 = createDeferredRequestPromise();
    const deferred6 = createDeferredRequestPromise();

    ResourceRequester.delete
        .mockReturnValueOnce(deferred1.promise)
        .mockReturnValueOnce(deferred2.promise)
        .mockReturnValueOnce(deferred3.promise)
        .mockReturnValueOnce(deferred4.promise)
        .mockReturnValueOnce(deferred5.promise)
        .mockReturnValueOnce(deferred6.promise);

    expect(getDeleteButton()).toBeEnabled();
    await user.click(getDeleteButton());
    expect(getDeleteButton()).toBeDisabled();
    expect(ResourceRequester.delete).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...defaultRequestOptions, id: 4});

    deferred1.resolve({});

    await waitFor(() => {
        expect(ResourceRequester.delete).toHaveBeenCalledTimes(4);
    });

    expect(getCancelButton()).toBeInTheDocument();
    await user.click(getCancelButton());

    expect(deferred1.promise.abort).not.toHaveBeenCalled();
    expect(deferred2.promise.abort).toHaveBeenCalled();
    expect(deferred3.promise.abort).toHaveBeenCalled();
    expect(deferred4.promise.abort).toHaveBeenCalled();
    expect(deferred5.promise.abort).not.toHaveBeenCalled();
    expect(deferred6.promise.abort).not.toHaveBeenCalled();

    expect(onCancel).toHaveBeenCalled();
    expect(onError).not.toHaveBeenCalled();
    expect(onFinish).not.toHaveBeenCalled();
});
