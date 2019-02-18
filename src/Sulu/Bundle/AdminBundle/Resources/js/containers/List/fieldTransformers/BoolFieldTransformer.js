// @flow
import React from 'react';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';
import Checkbox from '../../../components/Checkbox';

export default class BoolFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        return <Checkbox checked={!!value} disabled={true} />;
    }
}
