// @flow
import React from 'react';
import {render} from '@testing-library/react';
import PublishIndicator from '../PublishIndicator';

test('Show only the publish icon', () => {
    const {container} = render(<PublishIndicator published={true} />);

    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.published')).toBeInTheDocument();
    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.draft')).not.toBeInTheDocument();
});

test('Show only the draft icon', () => {
    const {container} = render(<PublishIndicator draft={true} />);

    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.draft')).toBeInTheDocument();
});

test('Show the draft and published icon', () => {
    const {container} = render(<PublishIndicator draft={true} published={true} />);

    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.published')).toBeInTheDocument();
    // eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.draft')).toBeInTheDocument();
});
