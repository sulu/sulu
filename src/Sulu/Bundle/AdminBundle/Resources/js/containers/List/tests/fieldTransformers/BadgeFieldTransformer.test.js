// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import {translate} from '../../../../utils/Translator';
import BadgeFieldTransformer from '../../fieldTransformers/BadgeFieldTransformer';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const badgeFieldTransformer = new BadgeFieldTransformer();
const parameters = {error: 'failed', prefix: 'status.', success: 'succeeded'};

test('Test empty values render nothing', () => {
    expect(badgeFieldTransformer.transform(undefined, parameters)).toBe(null);
    expect(badgeFieldTransformer.transform(null, parameters)).toBe(null);
    expect(badgeFieldTransformer.transform('', parameters)).toBe(null);
});

test('Test translated value with success and error variants', () => {
    // $FlowFixMe
    translate.mockImplementation((key) => key);

    render(<div>{badgeFieldTransformer.transform('succeeded', parameters)}</div>);
    render(<div>{badgeFieldTransformer.transform('failed', parameters)}</div>);
    render(<div>{badgeFieldTransformer.transform('running', parameters)}</div>);

    expect(screen.getByText('status.succeeded')).toHaveClass('success');
    expect(screen.getByText('status.failed')).toHaveClass('error');
    expect(screen.getByText('status.running')).not.toHaveClass('success', 'error');
});
