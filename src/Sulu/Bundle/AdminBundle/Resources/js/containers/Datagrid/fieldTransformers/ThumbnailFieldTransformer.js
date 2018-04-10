// @flow
import React from 'react';
import type {FieldTransformer} from '../types';

export default class ThumbnailFieldTransformer implements FieldTransformer {
    transform(value: *): * {
        if (!value) {
            return;
        }

        return <img src={value['sulu-40x40']} alt={value.alt} />;
    }
}
