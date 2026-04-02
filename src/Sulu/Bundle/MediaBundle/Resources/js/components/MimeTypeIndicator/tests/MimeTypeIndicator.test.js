// @flow
import {render} from '@testing-library/react';
import React from 'react';
import MimeTypeIndicator from '../MimeTypeIndicator';

test('Should render a MimeTypeIndicator', () => {
    const {asFragment} = render(<MimeTypeIndicator mimeType="application/vnd.ms-excel" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render a MimeTypeIndicator with different dimensions', () => {
    const {asFragment} = render(
        <MimeTypeIndicator
            height={200}
            iconSize={32}
            mimeType="application/vnd.ms-excel"
            width={200}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});
