// @flow
import React from 'react';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class ColorFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        return <div style={{'backgroundColor': value, 'width': '40px', 'height': '20px'}}></div>;
    }
}
