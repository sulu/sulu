// @flow
import React from 'react';
import {render} from 'enzyme';
import Column from '../Column';

test('Should render column with toolbar', () => {
    expect(render(<Column index={0} />)).toMatchSnapshot();
});
