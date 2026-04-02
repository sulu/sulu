// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Column from '../Column';

test('Should render column with toolbar', () => {
    const {asFragment} = render(<Column index={0} />);

    expect(asFragment()).toMatchSnapshot();
});
