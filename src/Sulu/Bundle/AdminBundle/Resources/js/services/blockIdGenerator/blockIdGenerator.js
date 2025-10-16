// @flow
import symfonyRouting from 'fos-jsrouting/router';
import ResourceRequester from '../../services/ResourceRequester';

/**
 * Generate a unique block ID from the backend API.
 *
 * @returns {Promise<string>} The generated block ID
 */
function generateBlockId(): Promise<string> {
    const url = symfonyRouting.generate('sulu_admin.post_block_ids');
    return ResourceRequester.post(url)
        .then((response) => {
            if (!response || !response.id) {
                throw new Error('Invalid response from block ID generator');
            }
            return response.id;
        });
}

export default {
    generateBlockId,
};
