// @flow
import React from 'react';
import {render} from 'enzyme';
import QRCode from '../QRCode';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('QRCode should render', () => {
    const onChange = jest.fn();
    expect(render(<QRCode
        disabled={false}
        onBlur={jest.fn()}
        onChange={onChange}
        valid={false}
        value="My value"
    />)).toMatchSnapshot();
});
