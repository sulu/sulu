// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Heading from '../../fields/Heading';

test('Render Toggler component as heading', () => {
    const formInspector = ({}: any);
    const schemaOptions = {
        description: {
            name: 'description',
            title: 'Hides a block',
        },
        icon: {
            name: 'icon',
            value: 'su-eye',
        },
        label: {
            name: 'label',
            title: 'Hide block',
        },
    };

    const {asFragment} = render(
        <Heading
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});
