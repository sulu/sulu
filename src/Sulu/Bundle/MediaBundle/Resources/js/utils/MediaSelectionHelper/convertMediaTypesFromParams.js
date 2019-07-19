//@flow
import type {SchemaOption} from 'sulu-admin-bundle/containers';
import type {MediaType} from '../../types';
import validateMediaType from './validateMediaType';

export default function convertMediaTypesFromParams(types: ?string): Array<MediaType> {
    if (!types) {
        return [];
    }

    return types.split(',').map((name) => {
        name = name.trim();

        if (!validateMediaType(name)) {
            throw new Error(
                'The parameter "types" contains the invalid value "' + (name || '') + '".'
            );
        }

        return name;
    });
}
