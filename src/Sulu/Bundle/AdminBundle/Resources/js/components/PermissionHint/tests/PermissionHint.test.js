// @flow
import React from 'react';
import {render} from '@testing-library/react';
import PermissionHint from '../PermissionHint';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render PermissionHint', () => {
    const {container} = render(<PermissionHint />);
    expect(container).toMatchSnapshot();
});
