// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Block from '../Block';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render an expanded block with multiple types', () => {
    const {container} = render(
        <Block
            activeType="type1"
            dragHandle={<span>Test</span>}
            expanded={true}
            icons={['su-eye', 'su-people']}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            onSettingsClick={jest.fn()}
            types={{'type1': 'Type1', 'type2': 'Type2'}}
        >
            Some block content
        </Block>);

    expect(container).toMatchSnapshot();
});
