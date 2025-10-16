// @flow
import symfonyRouting from 'fos-jsrouting/router';
import blockIdGenerator from '../blockIdGenerator';
import ResourceRequester from '../../ResourceRequester';

jest.mock('../../ResourceRequester', () => ({
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

test('Should generate block ID by calling the backend API', () => {
    const mockId = 'abc12345';
    const mockUrl = '/admin/api/block-ids.json';

    symfonyRouting.generate.mockReturnValue(mockUrl);
    ResourceRequester.post.mockReturnValue(Promise.resolve({id: mockId}));

    return blockIdGenerator.generateBlockId().then((id) => {
        expect(symfonyRouting.generate).toBeCalledWith('sulu_admin.post_block_ids');
        expect(ResourceRequester.post).toBeCalledWith(mockUrl);
        expect(id).toBe(mockId);
    });
});

test('Should throw error when response is missing', () => {
    const mockUrl = '/admin/api/block-ids.json';

    symfonyRouting.generate.mockReturnValue(mockUrl);
    ResourceRequester.post.mockReturnValue(Promise.resolve(null));

    return blockIdGenerator.generateBlockId().catch((error) => {
        expect(error.message).toBe('Invalid response from block ID generator');
    });
});

test('Should throw error when response is missing id property', () => {
    const mockUrl = '/admin/api/block-ids.json';

    symfonyRouting.generate.mockReturnValue(mockUrl);
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));

    return blockIdGenerator.generateBlockId().catch((error) => {
        expect(error.message).toBe('Invalid response from block ID generator');
    });
});

test('Should generate different IDs on multiple calls', () => {
    const mockUrl = '/admin/api/block-ids.json';
    const mockId1 = 'abc12345';
    const mockId2 = 'def67890';

    symfonyRouting.generate.mockReturnValue(mockUrl);
    ResourceRequester.post
        .mockReturnValueOnce(Promise.resolve({id: mockId1}))
        .mockReturnValueOnce(Promise.resolve({id: mockId2}));

    return Promise.all([
        blockIdGenerator.generateBlockId(),
        blockIdGenerator.generateBlockId(),
    ]).then(([id1, id2]) => {
        expect(id1).toBe(mockId1);
        expect(id2).toBe(mockId2);
        expect(id1).not.toBe(id2);
        expect(ResourceRequester.post).toHaveBeenCalledTimes(2);
    });
});

test('Should handle API errors gracefully', () => {
    const mockUrl = '/admin/api/block-ids.json';
    const mockError = new Error('Network error');

    symfonyRouting.generate.mockReturnValue(mockUrl);
    ResourceRequester.post.mockReturnValue(Promise.reject(mockError));

    return blockIdGenerator.generateBlockId().catch((error) => {
        expect(error).toBe(mockError);
    });
});
