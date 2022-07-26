// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
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

test('Render an block without dragHandle or collapse or expand button', () => {
    const {container} = render(
        <Block expanded={true}>
            Some block content
        </Block>
    );
    expect(container).toMatchSnapshot();
});

test('Render a collapsed block', () => {
    const {container} = render(
        <Block expanded={false} icons={['su-eye', 'su-people']} onCollapse={jest.fn()} onExpand={jest.fn()}>
            Some block content
        </Block>
    );
    expect(container).toMatchSnapshot();
});

test('Do not show type dropdown if only a single type is passed', () => {
    const {container} = render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    const element = container.querySelector('.select');
    expect(element).not.toBeInTheDocument();
});

test('Do not show remove icon if no onRemove prop has been passed', () => {
    render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(screen.queryByLabelText('su-trash-alt')).not.toBeInTheDocument();
});

test('Do not show settings icon if no onSettingsClick prop has been passed', () => {
    render(
        <Block expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} types={{'type': 'Type'}}>
            Some block content
        </Block>
    );

    expect(screen.queryByLabelText('su-cog')).not.toBeInTheDocument();
});

test('Clicking on a collapsed block should call the onExpand callback', () => {
    const expandSpy = jest.fn();
    render(<Block onCollapse={jest.fn()} onExpand={expandSpy}>Block content</Block>);

    screen.queryByRole('switch').click();

    expect(expandSpy).toHaveBeenCalledTimes(1);
});

test('Clicking on a expanded block should not call the onExpand callback', () => {
    const expandSpy = jest.fn();
    render(<Block expanded={true} onCollapse={jest.fn()} onExpand={expandSpy}>Block content</Block>);

    screen.queryByRole('switch').click();

    expect(expandSpy).toHaveBeenCalledTimes(1);
});
