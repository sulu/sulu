// @flow
import React from 'react';
import {render} from 'enzyme';
import Login from '../Login';

jest.mock('../../../utils', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.back_to_website':
                return 'Back to website';
        }
    },
}));

test('Should render the Login component', () => {
    expect(render(<Login onLogin={jest.fn()} onResetPassword={jest.fn()} />)).toMatchSnapshot();
});
