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

// Hands out predictable ids ('id-1', 'id-2', ...) so assertions can check assignment order.
function mockGenerator() {
    let counter = 0;

    return jest.spyOn(blockIdGenerator, 'generateBlockIds').mockImplementation(
        (count) => Promise.resolve(Array.from({length: count}, () => 'id-' + (++counter)))
    );
}

test('ensureBlockIds returns null and generates nothing when value is empty', async() => {
    const generateBlockIds = mockGenerator();

    expect(await blockIdGenerator.ensureBlockIds(undefined, {})).toEqual(null);
    expect(await blockIdGenerator.ensureBlockIds(null, {})).toEqual(null);
    expect(await blockIdGenerator.ensureBlockIds([], {})).toEqual(null);
    expect(generateBlockIds).not.toHaveBeenCalled();
});

test('ensureBlockIds returns null when every block already has an id', async() => {
    const generateBlockIds = mockGenerator();
    const types = {editor: {form: {}, title: 'Editor'}};
    const value = [
        {_id: 'existing-1', type: 'editor'},
        {_id: 'existing-2', type: 'editor'},
    ];

    expect(await blockIdGenerator.ensureBlockIds(value, types)).toEqual(null);
    expect(generateBlockIds).not.toHaveBeenCalled();
});

test('ensureBlockIds assigns ids to top-level blocks that miss one, keeping existing ids', async() => {
    const generateBlockIds = mockGenerator();
    const types = {editor: {form: {}, title: 'Editor'}};
    const value = [
        {_id: 'existing-1', type: 'editor'},
        {type: 'editor'},
        {_id: '', type: 'editor'},
    ];

    const result = await blockIdGenerator.ensureBlockIds(value, types);

    expect(generateBlockIds).toHaveBeenCalledWith(2);
    expect(result).toEqual([
        {_id: 'existing-1', type: 'editor'},
        {_id: 'id-1', type: 'editor'},
        {_id: 'id-2', type: 'editor'},
    ]);
});

test('ensureBlockIds assigns ids to blocks nested inside another block regardless of mount state', async() => {
    mockGenerator();
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

    const result = await blockIdGenerator.ensureBlockIds(value, types);

    expect(result[0]._id).toEqual('id-1');
    expect(result[0].children[0]._id).toEqual('id-2');
    expect(result[0].children[1]._id).toEqual('nested-existing');
});

test('ensureBlockIds assigns ids to image_map hotspots wrapped under a hotspots key', async() => {
    mockGenerator();
    const types = {default: {form: {}, title: 'Default'}};
    const value = {
        imageId: 5,
        hotspots: [
            {type: 'default', hotspot: {type: 'circle'}},
            {_id: 'hotspot-existing', type: 'default', hotspot: {type: 'circle'}},
        ],
    };

    const result = await blockIdGenerator.ensureBlockIds(value, types);

    expect(result.imageId).toEqual(5);
    expect(result.hotspots[0]._id).toEqual('id-1');
    expect(result.hotspots[1]._id).toEqual('hotspot-existing');
});

test('ensureBlockIds descends into fields nested in sections', async() => {
    mockGenerator();
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

    const result = await blockIdGenerator.ensureBlockIds(value, types);

    expect(result[0].children[0]._id).toEqual('id-2');
});

test('ensureBlockIds does not mutate the passed value', async() => {
    mockGenerator();
    const types = {editor: {form: {}, title: 'Editor'}};
    const value = [{type: 'editor'}];

    await blockIdGenerator.ensureBlockIds(value, types);

    expect(value).toEqual([{type: 'editor'}]);
});

test('ensureBlockIds returns null when the generator hands back an unexpected id count', async() => {
    jest.spyOn(blockIdGenerator, 'generateBlockIds').mockReturnValue(Promise.resolve(['only-one', 'too-many']));
    const types = {editor: {form: {}, title: 'Editor'}};
    const value = [{type: 'editor'}];

    expect(await blockIdGenerator.ensureBlockIds(value, types)).toEqual(null);
});
