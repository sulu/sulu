// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

const createDependantResourcesData = () => ({
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
});

const requestOptions = {
    foo: 'bar',
    locale: 'de',
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('The component should render', () => {
    const {baseElement} = render(
        <DeleteDependantResourcesDialog
            dependantResourcesData={createDependantResourcesData()}
            onCancel={jest.fn()}
            onError={jest.fn()}
            onFinish={jest.fn()}
            requestOptions={requestOptions}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('The component should call cancel callback', () => {
    const user = userEvent.setup();
    const onCancel = jest.fn();

    render(
        <DeleteDependantResourcesDialog
            dependantResourcesData={createDependantResourcesData()}
            onCancel={onCancel}
            onError={jest.fn()}
            onFinish={jest.fn()}
            requestOptions={requestOptions}
        />
    );

    return user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'})).then(() => {
        expect(onCancel).toHaveBeenCalled();
    });
});

test('The component should delete dependant resources', async() => {
    const user = userEvent.setup();
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();
    const dialogRef: any = React.createRef();

    render(
        <DeleteDependantResourcesDialog
            dependantResourcesData={createDependantResourcesData()}
            onCancel={onCancel}
            onError={onError}
            onFinish={onFinish}
            ref={dialogRef}
            requestOptions={requestOptions}
        />
    );

    const promise1 = RequestPromise.resolve({});
    const promise2 = RequestPromise.resolve({});
    const promise3 = RequestPromise.resolve({});
    const promise4 = RequestPromise.resolve({});
    const promise5 = RequestPromise.resolve({});
    const promise6 = RequestPromise.resolve({});

    ResourceRequester.delete
        .mockReturnValueOnce(promise1)
        .mockReturnValueOnce(promise2)
        .mockReturnValueOnce(promise3)
        .mockReturnValueOnce(promise4)
        .mockReturnValueOnce(promise5)
        .mockReturnValueOnce(promise6);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();

    await waitFor(() => expect(ResourceRequester.delete).toHaveBeenCalledTimes(6));
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(5, 'collections', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(6, 'media', {...requestOptions, id: 1});
    await waitFor(() => expect(dialogRef.current.totalDeletedResources).toBe(6));
    expect(await screen.findByRole('button', {name: 'sulu_admin.close'})).toBeInTheDocument();
    expect(onFinish).toHaveBeenCalled();
    expect(onError).not.toHaveBeenCalled();
    expect(onCancel).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.close'}));
    expect(onCancel).toHaveBeenCalled();
});

test('The component should reset itself when dependantResourcesData prop has changed', async() => {
    const user = userEvent.setup();
    const dialogRef: any = React.createRef();
    const initialDependantResourcesData = {
        dependantResourceBatches: [
            [
                {id: 1, resourceKey: 'media'},
            ],
        ],
        dependantResourcesCount: 1,
        detail: 'Detail',
        title: 'Title',
    };

    const {rerender} = render(
        <DeleteDependantResourcesDialog
            dependantResourcesData={initialDependantResourcesData}
            onCancel={jest.fn()}
            onError={jest.fn()}
            onFinish={jest.fn()}
            ref={dialogRef}
            requestOptions={requestOptions}
        />
    );

    ResourceRequester.delete.mockReturnValueOnce(RequestPromise.resolve({}));

    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));

    const newDependantResourcesData = {
        dependantResourceBatches: [],
        dependantResourcesCount: 0,
        detail: 'Detail',
        title: 'Title',
    };

    rerender(
        <DeleteDependantResourcesDialog
            dependantResourcesData={newDependantResourcesData}
            onCancel={jest.fn()}
            onError={jest.fn()}
            onFinish={jest.fn()}
            ref={dialogRef}
            requestOptions={requestOptions}
        />
    );

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
});

test('The component should reset itself when requestOptions prop has changed', async() => {
    const user = userEvent.setup();
    const dialogRef: any = React.createRef();
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

    const {rerender} = render(
        <DeleteDependantResourcesDialog
            dependantResourcesData={dependantResourcesData}
            onCancel={jest.fn()}
            onError={jest.fn()}
            onFinish={jest.fn()}
            ref={dialogRef}
            requestOptions={requestOptions}
        />
    );

    ResourceRequester.delete.mockReturnValueOnce(RequestPromise.resolve({}));

    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));

    rerender(
        <DeleteDependantResourcesDialog
            dependantResourcesData={dependantResourcesData}
            onCancel={jest.fn()}
            onError={jest.fn()}
            onFinish={jest.fn()}
            ref={dialogRef}
            requestOptions={{locale: 'en'}}
        />
    );

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
});

test('The component should call error callback', async() => {
    const user = userEvent.setup();
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();
    const dialogRef: any = React.createRef();

    render(
        <DeleteDependantResourcesDialog
            dependantResourcesData={createDependantResourcesData()}
            onCancel={onCancel}
            onError={onError}
            onFinish={onFinish}
            ref={dialogRef}
            requestOptions={requestOptions}
        />
    );

    const promise1 = RequestPromise.resolve({});
    const promise2 = RequestPromise.resolve({});
    const promise3 = RequestPromise.reject({
        json: () => Promise.resolve({message: 'Something really bad happened'}),
    });
    promise3.catch(() => undefined);
    const promise4 = RequestPromise.resolve({});

    ResourceRequester.delete
        .mockReturnValueOnce(promise1)
        .mockReturnValueOnce(promise2)
        .mockReturnValueOnce(promise3)
        .mockReturnValueOnce(promise4);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();

    await waitFor(() => expect(ResourceRequester.delete).toHaveBeenCalledTimes(4));
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...requestOptions, id: 3});
    await waitFor(() => expect(onError).toHaveBeenCalled());
    expect(await screen.findByRole('button', {name: 'sulu_admin.close'})).toBeInTheDocument();
    expect(onError).toHaveBeenCalled();
    expect(onFinish).not.toHaveBeenCalled();
    expect(onCancel).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.close'}));
    expect(onCancel).toHaveBeenCalled();
});

test('The component should abort requests on cancel', async() => {
    const user = userEvent.setup();
    const onCancel = jest.fn();
    const onError = jest.fn();
    const onFinish = jest.fn();
    const dialogRef: any = React.createRef();

    render(
        <DeleteDependantResourcesDialog
            dependantResourcesData={createDependantResourcesData()}
            onCancel={onCancel}
            onError={onError}
            onFinish={onFinish}
            ref={dialogRef}
            requestOptions={requestOptions}
        />
    );

    const promise1 = RequestPromise.resolve({});
    const promise2 = new RequestPromise(() => undefined);
    const promise3 = RequestPromise.resolve({});
    const promise4 = RequestPromise.resolve({});
    const promise5 = RequestPromise.resolve({});
    const promise6 = RequestPromise.resolve({});

    promise1.abort = jest.fn();
    promise2.abort = jest.fn();
    promise3.abort = jest.fn();
    promise4.abort = jest.fn();
    promise5.abort = jest.fn();
    promise6.abort = jest.fn();

    ResourceRequester.delete
        .mockReturnValueOnce(promise1)
        .mockReturnValueOnce(promise2)
        .mockReturnValueOnce(promise3)
        .mockReturnValueOnce(promise4)
        .mockReturnValueOnce(promise5)
        .mockReturnValueOnce(promise6);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();

    await waitFor(() => expect(ResourceRequester.delete).toHaveBeenCalledTimes(4));
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(1, 'media', {...requestOptions, id: 4});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(2, 'collections', {...requestOptions, id: 3});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(3, 'media', {...requestOptions, id: 2});
    expect(ResourceRequester.delete).toHaveBeenNthCalledWith(4, 'media', {...requestOptions, id: 3});

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

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
