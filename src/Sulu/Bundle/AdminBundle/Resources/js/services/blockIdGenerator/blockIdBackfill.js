// @flow
import {toJS} from 'mobx';
import blockIdGenerator from './blockIdGenerator';

// Reads and validates the shared "block_id_generator" schema option; `fieldType` tunes the error.
export function readBlockIdGeneratorOption(schemaOptions: Object, fieldType: string): ?boolean {
    const {
        block_id_generator: {
            value,
        } = {},
    } = schemaOptions;

    if (value !== undefined && typeof value !== 'boolean') {
        throw new Error(
            'The "' + fieldType + '" field type only accepts booleans as "block_id_generator" schema option!'
        );
    }

    return value;
}

// Fills a generated `_id` onto typed items (blocks, hotspots) that lack one and reports it via
// `onFilled`. Serialises overlapping runs; the caller gates on its own enabled flag.
export default function createBlockIdBackfiller(onFilled: (value: any) => void) {
    let generating = false;
    let pending: ?Object = null;

    const run = async(value: ?Object, types: ?Object): Promise<void> => {
        if (!value || !types) {
            return;
        }

        if (generating) {
            pending = value;

            return;
        }

        generating = true;
        try {
            const filledValue = await blockIdGenerator.ensureBlockIds(toJS(value), types);
            if (filledValue) {
                onFilled(filledValue);
            }
        } finally {
            generating = false;
        }

        if (pending) {
            const pendingValue = pending;
            pending = null;
            run(pendingValue, types);
        }
    };

    return run;
}
