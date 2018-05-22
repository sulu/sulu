// @flow
import {render} from 'enzyme';
import React from 'react';
import MimeTypeIndicator from '../MimeTypeIndicator';

test('Should render a MimeTypeIndicator', () => {
    expect(render(<MimeTypeIndicator mimeType="application/vnd.ms-excel" />)).toMatchSnapshot();
});

test('Should render a MimeTypeIndicator with different dimensions', () => {
    expect(render(
        <MimeTypeIndicator
            height={200}
            iconSize={32}
            mimeType="application/vnd.ms-excel"
            width={200}
        />
    )).toMatchSnapshot();
});
