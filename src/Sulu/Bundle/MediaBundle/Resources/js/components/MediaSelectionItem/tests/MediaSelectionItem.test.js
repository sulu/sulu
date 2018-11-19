// @flow
import {render} from 'enzyme';
import React from 'react';
import MediaSelectionItem from '../MediaSelectionItem';

test('Should render a MediaSelectionItem', () => {
    expect(render(
        <MediaSelectionItem
            mimeType="application/vnd.ms-excel"
            thumbnail="http://lorempixel.com/25/25"
        >
        test media
        </MediaSelectionItem>
    )).toMatchSnapshot();
});

test('Should render a MediaSelectionItem without thumbnail', () => {
    expect(render(
        <MediaSelectionItem
            mimeType="application/vnd.ms-excel"
        >
            test media
        </MediaSelectionItem>
    )).toMatchSnapshot();
});
