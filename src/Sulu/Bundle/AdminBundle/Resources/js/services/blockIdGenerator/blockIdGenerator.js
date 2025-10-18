// @flow
import symfonyRouting from 'fos-jsrouting/router';
import Requester from '../Requester';

/**
 * Generate unique block IDs from the backend API.
 *
 * @param {number} count - The number of IDs to generate (default: 1)
 * @returns {Promise<Array<string>>} Array of generated block IDs
 */
function generateBlockIds(count: number = 1): Promise<Array<string>> {
    if (count <= 0) {
        return Promise.resolve([]);
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
    generateBlockIds,
};
