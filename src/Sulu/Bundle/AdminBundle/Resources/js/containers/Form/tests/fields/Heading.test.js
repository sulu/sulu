// @flow
import React from 'react';
import {render} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Heading from '../../fields/Heading';

jest.mock('../../stores/ResourceFormStore', () => jest.fn());

test('Render Toggler component as heading', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
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

    expect(render(
        <Heading
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toMatchSnapshot();
});
