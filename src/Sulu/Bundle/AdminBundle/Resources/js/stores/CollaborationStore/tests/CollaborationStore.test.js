// @flow
import CollaborationStore from '../CollaborationStore';
import ResourceRequester from '../../../services/ResourceRequester';

jest.useFakeTimers();

jest.mock('../../../services/ResourceRequester', () => ({
    post: jest.fn(),
    delete: jest.fn(),
}));

test('Load collaborators repeatedly and stop when destroyed', () => {
    const collaborations1 = [
        {
            fullName: 'Max Mustermann',
        },
    ];

    const postPromise1 = Promise.resolve({
        _embedded: {
            collaborations: collaborations1,
        },
    });

    ResourceRequester.post.mockReturnValue(postPromise1);

    const collaborationStore = new CollaborationStore('pages', 1);

    expect(ResourceRequester.post).toHaveBeenLastCalledWith('collaborations', null, {id: 1, resourceKey: 'pages'});
    expect(ResourceRequester.post).toBeCalledTimes(1);

    return postPromise1.then(() => {
        expect(collaborationStore.collaborations).toEqual(collaborations1);

        const collaborations2 = [
            {
                fullName: 'Max Mustermann',
            },
            {
                fullName: 'Erika Mustermann',
            },
        ];

        const postPromise2 = Promise.resolve({
            _embedded: {
                collaborations: collaborations2,
            },
        });

        ResourceRequester.post.mockReturnValue(postPromise2);

        jest.runOnlyPendingTimers();
        expect(ResourceRequester.post).toHaveBeenLastCalledWith('collaborations', null, {id: 1, resourceKey: 'pages'});
        expect(ResourceRequester.post).toBeCalledTimes(2);

        return postPromise2.then(() => {
            expect(collaborationStore.collaborations).toEqual(collaborations2);

            collaborationStore.destroy();

            jest.runOnlyPendingTimers();
            expect(ResourceRequester.post).toBeCalledTimes(2);

            expect(ResourceRequester.delete).toBeCalledTimes(1);
            expect(ResourceRequester.delete).toHaveBeenLastCalledWith('collaborations', {id: 1, resourceKey: 'pages'});
        });
    });
});
