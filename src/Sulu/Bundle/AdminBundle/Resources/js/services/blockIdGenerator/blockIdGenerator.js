// @flow
import symfonyRouting from 'fos-jsrouting/router';
import Requester from '../Requester';

/**
 * Generate a unique block ID from the backend API.
 *
 * @returns {Promise<string>} The generated block ID
 */
function generateBlockId(): Promise<string> {
    const url = symfonyRouting.generate('sulu_admin.post_block_ids');
    return Requester.post(url)
        .then((response) => {
            if (!response || !response.id) {
                throw new Error('Invalid response from block ID generator');
            }
            return response.id;
        });
}

/**
 * Generate multiple unique block IDs from the backend API in a single request.
 *
 * @param {number} count - The number of IDs to generate
 * @returns {Promise<Array<string>>} Array of generated block IDs
 */
function generateBlockIds(count: number): Promise<Array<string>> {
    if (count <= 0) {
        return Promise.resolve([]);
    }

    if (count === 1) {
        return generateBlockId().then((id) => [id]);
    }

    const url = symfonyRouting.generate('sulu_admin.post_block_ids') + '?length=' + count;
    return Requester.post(url)
        .then((response) => {
            if (!response || !response._embedded || !response._embedded.blockIds) {
                throw new Error('Invalid response from block ID generator');
            }
            return response._embedded.blockIds.map((item) => item.id);
        });
}

export default {
    generateBlockId,
    generateBlockIds,
};
