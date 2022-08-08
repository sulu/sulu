// @flow
import React from 'react';
import {render} from '@testing-library/react';
import SegmentCounter from '../SegmentCounter';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should show a positive count if nothing is passed', () => {
    const {container} = render(<SegmentCounter delimiter="," max={20} value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Should show a positive count', () => {
    const {container} = render(<SegmentCounter delimiter="," max={5} value="keyword1, keyword2, keyword3" />);
    expect(container).toMatchSnapshot();
});

test('Should show a negative count', () => {
    const {container} = render(<SegmentCounter delimiter="|" max={2} value="That|is|a|test" />);
    expect(container).toMatchSnapshot();
});
