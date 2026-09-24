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

// Fills generated ids onto items that lack one. `getValue` is read again once the ids arrive, so
// only the new ids are merged in and anything typed during the request is kept. Serialises runs.
export default function createBlockIdBackfiller(onFilled: (value: any) => void) {
    let generating = false;
    let pending = false;

    const run = async(getValue: () => ?Object, types: ?Object): Promise<void> => {
        if (typeof getValue !== 'function' || !types) {
            return;
        }

        if (generating) {
            pending = true;

            return;
        }

        generating = true;
        try {
            const count = blockIdGenerator.countMissingBlockIds(toJS(getValue()), types);
            if (count > 0) {
                const ids = await blockIdGenerator.generateBlockIds(count);
                // Apply to the value as it is now, not the snapshot the run started with.
                const filledValue = blockIdGenerator.applyBlockIds(toJS(getValue()), types, ids);
                if (filledValue) {
                    onFilled(filledValue);
                }
            }
        } finally {
            generating = false;
        }

        if (pending) {
            pending = false;
            run(getValue, types);
        }
    };

    return run;
}
