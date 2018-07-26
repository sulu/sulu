// @flow
import React from 'react';
import {render} from 'enzyme';
import SegmentCounter from '../SegmentCounter';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should show a positive count if nothing is passed', () => {
    expect(render(<SegmentCounter delimiter="," max={20} value={undefined} />)).toMatchSnapshot();
});

test('Should show a positive count', () => {
    expect(render(<SegmentCounter delimiter="," max={5} value="keyword1, keyword2, keyword3" />)).toMatchSnapshot();
});

test('Should show a negative count', () => {
    expect(render(<SegmentCounter delimiter="|" max={2} value="That|is|a|test" />)).toMatchSnapshot();
});
