// @flow
import symfonyRouting from 'fos-jsrouting/router';
import Requester from '../Requester';

function isItemList(value: any): boolean {
    if (!Array.isArray(value) || value.length === 0) {
        return false;
    }

    return value.every((item) => !!item && typeof item === 'object' && !Array.isArray(item));
}

function collectFromItems(items: Array<any>, types: Object, pending: Array<Object>) {
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

function collectFromValue(value: any, types: Object, pending: Array<Object>) {
    if (isItemList(value)) {
        collectFromItems(value, types, pending);

        return;
    }

    if (value && typeof value === 'object' && !Array.isArray(value)) {
        Object.keys(value).forEach((key) => {
            if (isItemList(value[key])) {
                collectFromItems(value[key], types, pending);
            }
        });
    }
}

function collectFromSchema(schema: Object, data: any, pending: Array<Object>) {
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
    // Fetches `count` freshly generated block ids from the backend.
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

    // Counts the typed items in `value` that still lack an `_id`.
    countMissingBlockIds(value: any, types: Object): number {
        if (value === undefined || value === null) {
            return 0;
        }

        const missing = [];
        collectFromValue(value, types, missing);

        return missing.length;
    },

    // Clones `value` and hands the given ids, in order, to the items still missing one.
    applyBlockIds(value: any, types: Object, ids: Array<string>): any {
        if (value === undefined || value === null || !Array.isArray(ids) || ids.length === 0) {
            return null;
        }

        const clone = deepClone(value);
        const pending = [];
        collectFromValue(clone, types, pending);

        const count = Math.min(pending.length, ids.length);
        if (count === 0) {
            return null;
        }

        for (let index = 0; index < count; index++) {
            pending[index]._id = ids[index];
        }

        return clone;
    },
};

export default blockIdGenerator;
