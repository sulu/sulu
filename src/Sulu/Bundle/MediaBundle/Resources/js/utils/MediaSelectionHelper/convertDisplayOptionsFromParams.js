//@flow
import type {SchemaOption} from 'sulu-admin-bundle/containers';
import type {DisplayOption} from '../../types';
import validateDisplayOption from './validateDisplayOption';

export default function convertDisplayOptionsFromParams(displayOptions: ?Array<SchemaOption>): Array<DisplayOption> {
    if (!displayOptions) {
        return [];
    }

    return displayOptions
        .filter((displayOption) => displayOption.value === true)
        .map(({name}) => {
            if (!validateDisplayOption(name)) {
                throw new Error(
                    'The children of "displayOptions" contains the invalid value "' + (name || '') + '".'
                );
            }
            return name;
        });
}
