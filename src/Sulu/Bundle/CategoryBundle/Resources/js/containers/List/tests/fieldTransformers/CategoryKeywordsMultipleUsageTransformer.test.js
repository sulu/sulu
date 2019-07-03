// @flow
import React from 'react';
import {Checkbox} from 'sulu-admin-bundle/components';
import CategoryKeywordsMultipleUsageTransformer from '../../fieldTransformers/CategoryKeywordsMultipleUsageTransformer';

const categoryKeywordsMultipleUsageTransformer = new CategoryKeywordsMultipleUsageTransformer();

test.each([
    [undefined, false],
    [0, false],
    [1, false],
    [2, true],
    [100, true],
    ['0', false],
    ['1', false],
    ['2', true],
])('Transform %s', (value, checked) => {
    expect(categoryKeywordsMultipleUsageTransformer.transform(value))
        .toEqual(<Checkbox checked={checked} disabled={true} />);
});
