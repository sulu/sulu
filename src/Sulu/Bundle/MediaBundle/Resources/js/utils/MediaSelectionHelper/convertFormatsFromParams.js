//@flow
import type {SchemaOption} from 'sulu-admin-bundle/containers';

export default function convertFormatsFromParams(formats: ?Array<SchemaOption>): Array<string> {
    if (!formats) {
        return [];
    }

    return formats.map(({name}) => String(name));
}
