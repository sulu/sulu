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

test('countMissingBlockIds returns 0 for an empty value', () => {
    expect(blockIdGenerator.countMissingBlockIds(undefined, {})).toEqual(0);
    expect(blockIdGenerator.countMissingBlockIds(null, {})).toEqual(0);
    expect(blockIdGenerator.countMissingBlockIds([], {})).toEqual(0);
});

test('countMissingBlockIds and applyBlockIds handle blocks nested inside another block', () => {
    const types = {
        container: {
            title: 'Container',
            form: {
                children: {
                    type: 'block',
                    types: {
                        editor: {form: {}, title: 'Editor'},
                    },
                },
            },
        },
    };
    const value = [
        {
            type: 'container',
            children: [
                {type: 'editor'},
                {_id: 'nested-existing', type: 'editor'},
            ],
        },
    ];

    expect(blockIdGenerator.countMissingBlockIds(value, types)).toEqual(2);

    const result = blockIdGenerator.applyBlockIds(value, types, ['id-1', 'id-2']);

    expect(result[0]._id).toEqual('id-1');
    expect(result[0].children[0]._id).toEqual('id-2');
    expect(result[0].children[1]._id).toEqual('nested-existing');
});

test('countMissingBlockIds and applyBlockIds handle image_map hotspots wrapped under a hotspots key', () => {
    const types = {default: {form: {}, title: 'Default'}};
    const value = {
        imageId: 5,
        hotspots: [
            {type: 'default', hotspot: {type: 'circle'}},
            {_id: 'hotspot-existing', type: 'default', hotspot: {type: 'circle'}},
        ],
    };

    expect(blockIdGenerator.countMissingBlockIds(value, types)).toEqual(1);

    const result = blockIdGenerator.applyBlockIds(value, types, ['id-1']);

    expect(result.imageId).toEqual(5);
    expect(result.hotspots[0]._id).toEqual('id-1');
    expect(result.hotspots[1]._id).toEqual('hotspot-existing');
});

test('countMissingBlockIds and applyBlockIds descend into fields nested in sections', () => {
    const types = {
        container: {
            title: 'Container',
            form: {
                section: {
                    type: 'section',
                    items: {
                        children: {
                            type: 'block',
                            types: {editor: {form: {}, title: 'Editor'}},
                        },
                    },
                },
            },
        },
    };
    const value = [
        {type: 'container', children: [{type: 'editor'}]},
    ];

    expect(blockIdGenerator.countMissingBlockIds(value, types)).toEqual(2);

    const result = blockIdGenerator.applyBlockIds(value, types, ['id-1', 'id-2']);

    expect(result[0].children[0]._id).toEqual('id-2');
});

test('countMissingBlockIds counts only the items without an id', () => {
    const types = {editor: {form: {}, title: 'Editor'}};

    expect(blockIdGenerator.countMissingBlockIds(undefined, types)).toEqual(0);
    expect(blockIdGenerator.countMissingBlockIds([{_id: 'a', type: 'editor'}], types)).toEqual(0);
    expect(blockIdGenerator.countMissingBlockIds(
        [{type: 'editor'}, {_id: '', type: 'editor'}, {_id: 'a', type: 'editor'}],
        types
    )).toEqual(2);
});

test('applyBlockIds fills the still-missing items and does not mutate the passed value', () => {
    const types = {editor: {form: {}, title: 'Editor'}};
    const value = [{type: 'editor'}, {_id: 'kept', type: 'editor'}, {type: 'editor'}];

    const result = blockIdGenerator.applyBlockIds(value, types, ['id-1', 'id-2']);

    expect(result).toEqual([
        {_id: 'id-1', type: 'editor'},
        {_id: 'kept', type: 'editor'},
        {_id: 'id-2', type: 'editor'},
    ]);
    expect(value).toEqual([{type: 'editor'}, {_id: 'kept', type: 'editor'}, {type: 'editor'}]);
});

test('applyBlockIds fills only as many items as it has ids when more went missing meanwhile', () => {
    const types = {editor: {form: {}, title: 'Editor'}};
    const value = [{type: 'editor'}, {type: 'editor'}];

    const result = blockIdGenerator.applyBlockIds(value, types, ['id-1']);

    expect(result[0]._id).toEqual('id-1');
    expect(result[1]._id).toBeUndefined();
});

test('applyBlockIds returns null when there is nothing to fill', () => {
    const types = {editor: {form: {}, title: 'Editor'}};

    expect(blockIdGenerator.applyBlockIds([{_id: 'a', type: 'editor'}], types, ['id-1'])).toEqual(null);
    expect(blockIdGenerator.applyBlockIds([{type: 'editor'}], types, [])).toEqual(null);
    expect(blockIdGenerator.applyBlockIds(null, types, ['id-1'])).toEqual(null);
});
