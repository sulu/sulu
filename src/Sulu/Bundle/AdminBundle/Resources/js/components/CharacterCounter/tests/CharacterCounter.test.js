// @flow
import React from 'react';
import {render} from 'enzyme';
import CharacterCounter from '../CharacterCounter';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should show a positive count if nothing is passed', () => {
    expect(render(<CharacterCounter max={20} value={undefined} />)).toMatchSnapshot();
});

test('Should show a positive count', () => {
    expect(render(<CharacterCounter max={20} value="That's a test" />)).toMatchSnapshot();
});

test('Should show a negative count', () => {
    expect(render(<CharacterCounter max={5} value="That's a test" />)).toMatchSnapshot();
});

test('Should show a positive count with numbers', () => {
    expect(render(<CharacterCounter max={5} value={123} />)).toMatchSnapshot();
});

test('Should show a negative count with numbers', () => {
    expect(render(<CharacterCounter max={5} value={123456} />)).toMatchSnapshot();
});
