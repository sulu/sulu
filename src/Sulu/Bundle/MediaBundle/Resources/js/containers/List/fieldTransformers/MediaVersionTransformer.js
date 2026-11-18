// @flow
import {translate} from 'sulu-admin-bundle/utils';
import type {Node} from 'react';
import type {FieldTransformer} from 'sulu-admin-bundle/types';

/**
 * @experimental
 */
export default class MediaVersionTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (value === undefined || value === null) {
            return null;
        }

        return translate('sulu_media.version') + ' ' + value;
    }
}
