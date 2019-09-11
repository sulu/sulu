// @flow
import CollaborationStore from '../CollaborationStore';
import ResourceRequester from '../../../services/ResourceRequester';

jest.useFakeTimers();

jest.mock('../../../services/ResourceRequester', () => ({
    put: jest.fn(),
    delete: jest.fn(),
}));

test('Load collaborators repeatedly and stop when destroyed', () => {
    const collaborations1 = [
        {
            fullName: 'Max Mustermann',
        },
    ];

    const putPromise1 = Promise.resolve({
        _embedded: {
            collaborations: collaborations1,
        },
    });

    ResourceRequester.put.mockReturnValue(putPromise1);

    const collaborationStore = new CollaborationStore('pages', 1);

    expect(ResourceRequester.put).toHaveBeenLastCalledWith('collaborations', null, {id: 1, resourceKey: 'pages'});
    expect(ResourceRequester.put).toBeCalledTimes(1);

    return putPromise1.then(() => {
        expect(collaborationStore.collaborations).toEqual(collaborations1);

        const collaborations2 = [
            {
                fullName: 'Max Mustermann',
            },
            {
                fullName: 'Erika Mustermann',
            },
        ];

        const putPromise2 = Promise.resolve({
            _embedded: {
                collaborations: collaborations2,
            },
        });

        ResourceRequester.put.mockReturnValue(putPromise2);

        jest.runOnlyPendingTimers();
        expect(ResourceRequester.put).toHaveBeenLastCalledWith('collaborations', null, {id: 1, resourceKey: 'pages'});
        expect(ResourceRequester.put).toBeCalledTimes(2);

        return putPromise2.then(() => {
            expect(collaborationStore.collaborations).toEqual(collaborations2);

            collaborationStore.destroy();

            jest.runOnlyPendingTimers();
            expect(ResourceRequester.put).toBeCalledTimes(2);

            expect(ResourceRequester.delete).toBeCalledTimes(1);
            expect(ResourceRequester.delete).toHaveBeenLastCalledWith('collaborations', {id: 1, resourceKey: 'pages'});
        });
    });
});
