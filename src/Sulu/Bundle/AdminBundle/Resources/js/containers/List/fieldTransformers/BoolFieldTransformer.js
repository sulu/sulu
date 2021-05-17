// @flow
import React from 'react';
import Checkbox from '../../../components/Checkbox';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class BoolFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        return <Checkbox checked={!!value} disabled={true} />;
    }
}
