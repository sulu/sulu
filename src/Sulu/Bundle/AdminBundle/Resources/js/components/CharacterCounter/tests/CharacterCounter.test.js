// @flow
import React from 'react';
import {render} from '@testing-library/react';
import CharacterCounter from '../CharacterCounter';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should show a positive count if nothing is passed', () => {
    const {container} = render(<CharacterCounter max={20} value={undefined} />);
    expect(container).toMatchSnapshot();
});

test('Should show a positive count', () => {
    const {container} = render(<CharacterCounter max={20} value="That's a test" />);
    expect(container).toMatchSnapshot();
});

test('Should show a negative count', () => {
    const {container} = render(<CharacterCounter max={5} value="That's a test" />);
    expect(container).toMatchSnapshot();
});

test('Should show a positive count with numbers', () => {
    const {container} = render(<CharacterCounter max={5} value={123} />);
    expect(container).toMatchSnapshot();
});

test('Should show a negative count with numbers', () => {
    const {container} = render(<CharacterCounter max={5} value={123456} />);
    expect(container).toMatchSnapshot();
});
