// @flow
import React from 'react';
import {Checkbox} from 'sulu-admin-bundle/components';
import type {Node} from 'react';
import type {FieldTransformer} from 'sulu-admin-bundle/types';

export default class CategoryKeywordsMultipleUsageTransformer implements FieldTransformer {
    transform(value: *): Node {
        return <Checkbox checked={value > 1} disabled={true} />;
    }
}
