// @flow
import React from 'react';
import {render} from 'enzyme';
import PermissionHint from '../PermissionHint';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render PermissionHint', () => {
    expect(render(<PermissionHint />)).toMatchSnapshot();
});
