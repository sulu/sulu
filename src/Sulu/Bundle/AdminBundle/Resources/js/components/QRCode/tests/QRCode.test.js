// @flow
import React from 'react';
import {render} from '@testing-library/react';
import QRCode from '../QRCode';

test('QRCode should render', () => {
    const onChange = jest.fn();
    const {container} = render(<QRCode
        disabled={false}
        onBlur={jest.fn()}
        onChange={onChange}
        valid={false}
        value="My value"
    />);
    expect(container).toMatchSnapshot();
});
