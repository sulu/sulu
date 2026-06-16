// @flow
import symfonyRouting from 'fos-jsrouting/router';
import blockIdGenerator from '../blockIdGenerator';
import Requester from '../../Requester';

jest.mock('../../Requester', () => ({
    __esModule: true,
    default: {
        post: jest.fn(),
    },
}));

jest.mock('fos-jsrouting/router', () => ({
    generate: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should generate single block ID by calling the backend API', () => {
    const mockId = 'abc12345';
    const mockUrl = '/admin/api/block-ids.json';

    symfonyRouting.generate.mockReturnValue(mockUrl);
    Requester.post.mockReturnValue(Promise.resolve({
        _embedded: {
            blockIds: [{id: mockId}],
        },
    }));

    return blockIdGenerator.generateBlockIds(1).then((ids) => {
        expect(symfonyRouting.generate).toHaveBeenCalledWith('sulu_admin.post_block_ids');
        expect(Requester.post).toHaveBeenCalledWith(mockUrl + '?length=1');
        expect(ids).toEqual([mockId]);
    });
});

test('Should generate multiple block IDs in a single request', () => {
    const mockId1 = 'abc12345';
    const mockId2 = 'def67890';
    const mockId3 = 'ghi13579';
    const mockUrl = '/admin/api/block-ids.json';

    symfonyRouting.generate.mockReturnValue(mockUrl);
    Requester.post.mockReturnValue(Promise.resolve({
        _embedded: {
            blockIds: [
                {id: mockId1},
                {id: mockId2},
                {id: mockId3},
            ],
        },
    }));

    return blockIdGenerator.generateBlockIds(3).then((ids) => {
        expect(symfonyRouting.generate).toHaveBeenCalledWith('sulu_admin.post_block_ids');
        expect(Requester.post).toHaveBeenCalledWith(mockUrl + '?length=3');
        expect(ids).toEqual([mockId1, mockId2, mockId3]);
    });
});

test('Should return empty array when count is 0', () => {
    return blockIdGenerator.generateBlockIds(0).then((ids) => {
        expect(ids).toEqual([]);
        expect(Requester.post).not.toHaveBeenCalled();
    });
});

test('Should throw error when response is missing', () => {
    const mockUrl = '/admin/api/block-ids.json';

    symfonyRouting.generate.mockReturnValue(mockUrl);
    Requester.post.mockReturnValue(Promise.resolve(null));

    return blockIdGenerator.generateBlockIds(1).catch((error) => {
        expect(error.message).toBe('Invalid response from block ID generator');
    });
});

test('Should throw error when response is missing _embedded structure', () => {
    const mockUrl = '/admin/api/block-ids.json';

    symfonyRouting.generate.mockReturnValue(mockUrl);
    Requester.post.mockReturnValue(Promise.resolve({}));

    return blockIdGenerator.generateBlockIds(1).catch((error) => {
        expect(error.message).toBe('Invalid response from block ID generator');
    });
});

test('Should handle API errors gracefully', () => {
    const mockUrl = '/admin/api/block-ids.json';
    const mockError = new Error('Network error');

    symfonyRouting.generate.mockReturnValue(mockUrl);
    Requester.post.mockReturnValue(Promise.reject(mockError));

    return blockIdGenerator.generateBlockIds(1).catch((error) => {
        expect(error).toBe(mockError);
    });
});
