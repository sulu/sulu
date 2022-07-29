/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from '@testing-library/react';
import CroppedText from '../CroppedText';

test('CroppedText should render', () => {
    const {container} = render(<CroppedText>This is a text which will get cropped.</CroppedText>);
    expect(container).toMatchSnapshot();
});
