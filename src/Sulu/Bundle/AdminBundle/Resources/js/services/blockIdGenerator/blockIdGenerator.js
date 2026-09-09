// @flow
import symfonyRouting from 'fos-jsrouting/router';
import Requester from '../Requester';
import type {Schema, Types} from '../../containers/Form/types';

// A "typed item" is an object stored inside a block/image_map list: it carries a `type` selecting
// its sub-form and should carry a stable `_id`.
function isItemList(value: any): boolean {
    if (!Array.isArray(value) || value.length === 0) {
        return false;
    }

    return value.every((item) => !!item && typeof item === 'object' && !Array.isArray(item));
}

function collectFromItems(items: Array<any>, types: Types, pending: Array<Object>) {
    items.forEach((item) => {
        if (typeof item._id !== 'string' || item._id === '') {
            pending.push(item);
        }

        const type = typeof item.type === 'string' ? item.type : undefined;
        const typeForm = type && types[type] ? types[type].form : undefined;

        if (typeForm) {
            collectFromSchema(typeForm, item, pending);
        }
    });
}

function collectFromValue(value: any, types: Types, pending: Array<Object>) {
    if (isItemList(value)) {
        collectFromItems(value, types, pending);

        return;
    }

    // A typed property does not always store its items directly as the value - image_map for
    // example wraps them under a "hotspots" key alongside its own "imageId". The wrapping key is
    // part of each field's storage format, not the schema, so scan the value's own array entries
    // for the item list instead of hardcoding known keys.
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        Object.keys(value).forEach((key) => {
            if (isItemList(value[key])) {
                collectFromItems(value[key], types, pending);
            }
        });
    }
}

function collectFromSchema(schema: Schema, data: any, pending: Array<Object>) {
    if (!data || typeof data !== 'object') {
        return;
    }

    Object.keys(schema).forEach((name) => {
        const entry = schema[name];

        // Sections only group fields visually; their nested fields live at the same data level.
        if (entry.items) {
            collectFromSchema(entry.items, data, pending);
        }

        if (entry.types && data[name] !== undefined) {
            collectFromValue(data[name], entry.types, pending);
        }
    });
}

function deepClone(value: any): any {
    if (Array.isArray(value)) {
        return value.map(deepClone);
    }

    if (value && typeof value === 'object') {
        const clone = {};
        Object.keys(value).forEach((key) => {
            clone[key] = deepClone(value[key]);
        });

        return clone;
    }

    return value;
}

const blockIdGenerator = {
    /**
     * Generate unique block IDs from the backend API.
     *
     * @param {number} count - The number of IDs to generate (default: 1)
     * @returns {Promise<Array<string>>} Array of generated block IDs
     */
    generateBlockIds(count: number = 1): Promise<Array<string>> {
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
    },

    // Recursively assigns a generated `_id` to every typed item (block, image_map hotspot and any
    // nested variant) that does not have one yet, descending into each item's sub-form via `types`.
    // Resolves to a new value with the ids filled in, or null when every item already had one, so
    // callers can skip a redundant onChange. Works on the raw data instead of the mounted
    // components, so ids exist for the preview-to-admin navigation even for blocks inside a
    // collapsed/never-expanded ancestor.
    async ensureBlockIds(value: any, types: Types): Promise<any> {
        if (value === undefined || value === null) {
            return null;
        }

        const clone = deepClone(value);
        const pending = [];
        collectFromValue(clone, types, pending);

        if (pending.length === 0) {
            return null;
        }

        const ids = await this.generateBlockIds(pending.length);

        if (!Array.isArray(ids) || ids.length !== pending.length) {
            return null;
        }

        pending.forEach((item, index) => {
            item._id = ids[index];
        });

        return clone;
    },
};

export default blockIdGenerator;
