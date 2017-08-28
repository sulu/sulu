/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import CroppedText from '../CroppedText';

test('CroppedText should render', () => {
    expect(render(<CroppedText>This is a text which will get cropped.</CroppedText>)).toMatchSnapshot();
});
